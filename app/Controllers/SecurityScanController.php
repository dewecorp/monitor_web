<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\User;
use App\Services\BackdoorScanner;

class SecurityScanController
{
    public function index(): string
    {
        Auth::check();
        return view('security_scan.index', [
            'pageTitle' => 'Security Scan',
            'results' => null,
            'scanPath' => '',
        ]);
    }

    public function run(): string
    {
        Auth::check();

        $scanPath = $_POST['path'] ?? BASE_PATH;
        $scanner = new BackdoorScanner($scanPath);
        $results = $scanner->scan();

        User::logActivity(
            $_SESSION['user_id'],
            'Security Scan',
            "Scan: {$results['total_files']} files, {$results['scanned_files']} PHP files. Score: {$results['malware_score']}%"
        );

        return view('security_scan.index', [
            'pageTitle' => 'Security Scan',
            'results' => $results,
            'scanPath' => $scanPath,
        ]);
    }

    public function apiScan(): void
    {
        Auth::check();
        $scanPath = $_GET['path'] ?? BASE_PATH;
        $scanner = new BackdoorScanner($scanPath);
        $results = $scanner->scan();
        jsonResponse(['success' => true, 'data' => $results]);
    }

    public function quarantine(): void
    {
        Auth::check();
        $file = $_POST['file'] ?? '';
        $action = $_POST['action'] ?? '';

        if (!$file || !file_exists($file)) {
            $_SESSION['error'] = 'File tidak ditemukan';
            redirect('/security-scan');
        }

        $quarantineDir = STORAGE_PATH . '/quarantine/' . date('Y-m-d');
        if (!is_dir($quarantineDir)) {
            mkdir($quarantineDir, 0755, true);
        }

        $filename = basename($file);
        $dest = $quarantineDir . '/' . date('His') . '_' . $filename;
        $hash = md5_file($file);

        if ($action === 'quarantine') {
            if (rename($file, $dest)) {
                file_put_contents($dest . '.meta', json_encode([
                    'original' => $file,
                    'quarantined_at' => date('Y-m-d H:i:s'),
                    'hash' => $hash,
                    'quarantined_by' => $_SESSION['user_nama'] ?? 'Unknown',
                ]));
                User::logActivity($_SESSION['user_id'], 'Quarantine File', "Memindahkan file ke karantina: {$file}");
                $_SESSION['success'] = "File {$filename} dipindahkan ke karantina";
            } else {
                $_SESSION['error'] = "Gagal memindahkan file {$filename}";
            }
        } elseif ($action === 'delete') {
            if (unlink($file)) {
                User::logActivity($_SESSION['user_id'], 'Delete Suspicious', "Menghapus file mencurigakan: {$file}");
                $_SESSION['success'] = "File {$filename} berhasil dihapus";
            } else {
                $_SESSION['error'] = "Gagal menghapus file {$filename}";
            }
        }

        redirect('/security-scan');
    }
}
