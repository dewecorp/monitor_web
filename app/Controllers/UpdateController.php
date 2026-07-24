<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\User;

class UpdateController
{
    private string $repoUrl = 'https://api.github.com/repos/dewecorp/monitor_web/releases/latest';
    private string $zipUrl = 'https://github.com/dewecorp/monitor_web/archive/refs/heads/master.zip';
    private string $tempDir;

    public function __construct()
    {
        $this->tempDir = STORAGE_PATH . '/update_temp';
    }

    public function check(): void
    {
        // Only check on remote servers, not localhost
        if ($this->isLocal()) {
            jsonResponse(['update_available' => false, 'message' => 'Lokal']);
        }

        $latest = $this->getLatestVersion();
        if (!$latest) {
            jsonResponse(['update_available' => false, 'message' => 'Gagal cek update']);
        }

        $currentVer = $this->getCurrentVersion();
        $hasUpdate = version_compare($latest['tag_name'] ?? '0', $currentVer, '>');

        jsonResponse([
            'update_available' => $hasUpdate,
            'version' => $latest['tag_name'] ?? '',
            'message' => $hasUpdate ? "Versi {$latest['tag_name']} tersedia" : 'Aplikasi sudah terbaru',
        ]);
    }

    public function run(): void
    {
        Auth::check();

        if ($this->isLocal()) {
            $_SESSION['error'] = 'Update hanya untuk hosting, bukan localhost';
            redirect('/');
        }

        try {
            // Download ZIP
            $zipPath = $this->downloadZip();
            if (!$zipPath) throw new \Exception('Gagal download file update');

            // Extract
            $extractPath = $this->extractZip($zipPath);
            if (!$extractPath) throw new \Exception('Gagal mengekstrak file');

            // Copy files
            $this->copyFiles($extractPath);

            // Cleanup
            $this->cleanup();

            User::logActivity($_SESSION['user_id'], 'Update Sistem', 'Update sistem berhasil');
            $_SESSION['success'] = 'Sistem berhasil diperbarui!';
        } catch (\Throwable $e) {
            $this->cleanup();
            $_SESSION['error'] = 'Update gagal: ' . $e->getMessage();
        }

        redirect('/');
    }

    private function isLocal(): bool
    {
        $host = $_SERVER['SERVER_NAME'] ?? '';
        return in_array($host, ['localhost', '127.0.0.1', '::1']);
    }

    private function getCurrentVersion(): string
    {
        $verFile = BASE_PATH . '/version.txt';
        if (file_exists($verFile)) {
            return trim(file_get_contents($verFile));
        }
        return '1.0.0';
    }

    private function getLatestVersion(): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->repoUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'WEBGUARDIAN-Updater/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github.v3+json'],
        ]);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http !== 200) return null;
        return json_decode($resp, true);
    }

    private function downloadZip(): ?string
    {
        if (!is_dir($this->tempDir)) mkdir($this->tempDir, 0755, true);
        $zipPath = $this->tempDir . '/update.zip';

        $ch = curl_init();
        $fp = fopen($zipPath, 'w');
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->zipUrl,
            CURLOPT_FILE => $fp,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'WEBGUARDIAN-Updater/1.0',
        ]);
        curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        return ($http === 200) ? $zipPath : null;
    }

    private function extractZip(string $zipPath): ?string
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) return null;

        $extractTo = $this->tempDir . '/extract';
        if (!is_dir($extractTo)) mkdir($extractTo, 0755, true);

        $zip->extractTo($extractTo);
        $zip->close();

        // Find the extracted directory
        $items = scandir($extractTo);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..' && is_dir($extractTo . '/' . $item)) {
                return $extractTo . '/' . $item;
            }
        }
        return null;
    }

    private function copyFiles(string $source): void
    {
        $exclude = ['.git', 'vendor', 'node_modules', 'storage', '.env', 'git-commit-push-backup.bat'];
        $this->copyDir($source, BASE_PATH, $exclude);
    }

    private function copyDir(string $src, string $dst, array $exclude): void
    {
        $items = scandir($src);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            if (in_array($item, $exclude)) continue;

            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;

            if (is_dir($srcPath)) {
                if (!is_dir($dstPath)) mkdir($dstPath, 0755, true);
                $this->copyDir($srcPath, $dstPath, $exclude);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }

    private function cleanup(): void
    {
        $this->deleteDir($this->tempDir);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
