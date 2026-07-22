<?php
declare(strict_types=1);

namespace App\Services;

class FileIntegrityMonitor
{
    private int $websiteId;
    private string $basePath;
    private array $results = [
        'scanned' => 0,
        'matched' => 0,  // unchanged
        'modified' => 0,
        'added' => 0,
        'deleted' => 0,
        'errors' => 0,
        'changes' => [],
    ];

    private array $includeExts = ['php', 'phtml', 'html', 'js', 'css', 'json', 'xml', 'yml', 'yaml', 'env', 'htaccess'];
    private array $excludeDirs = ['vendor', 'node_modules', '.git', '.svn', 'cache', 'storage/logs', 'storage/cache'];

    public function __construct(int $websiteId, string $basePath)
    {
        $this->websiteId = $websiteId;
        $this->basePath = rtrim($basePath, '/\\');
    }

    public function run(): array
    {
        $db = \App\Config\Database::getConnection();
        $currentFiles = $this->scanFiles();

        $baseline = $db->prepare("SELECT file_path, sha256 FROM file_checksums WHERE website_id = ? AND status = 'baseline'");
        $baseline->execute([$this->websiteId]);
        $baselineMap = [];
        foreach ($baseline->fetchAll() as $row) {
            $baselineMap[$row['file_path']] = $row['sha256'];
        }

        $insertStmt = $db->prepare("INSERT INTO file_checksums (website_id, file_path, sha256, file_size, last_modified, first_seen) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE sha256 = VALUES(sha256), file_size = VALUES(file_size), last_modified = VALUES(last_modified), last_seen = NOW()");
        $changeInsert = $db->prepare("INSERT INTO file_changes (website_id, file_path, change_type, old_sha256, new_sha256, old_size, new_size, diff_preview) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        // Check current files against baseline
        foreach ($currentFiles as $path => $info) {
            $this->results['scanned']++;
            $insertStmt->execute([$this->websiteId, $path, $info['sha256'], $info['size'], $info['mtime']]);

            if (isset($baselineMap[$path])) {
                if ($baselineMap[$path] !== $info['sha256']) {
                    $this->results['modified']++;
                    $diff = $this->generateDiff($this->basePath . '/' . $path, $baselineMap[$path]);
                    $changeInsert->execute([$this->websiteId, $path, 'modified', $baselineMap[$path], $info['sha256'], $info['size'], $info['size'], $diff]);
                    $this->results['changes'][] = [
                        'file' => $path, 'type' => 'modified',
                        'old_hash' => $baselineMap[$path], 'new_hash' => $info['sha256'],
                    ];
                } else {
                    $this->results['matched']++;
                }
            } else {
                $this->results['added']++;
                $changeInsert->execute([$this->websiteId, $path, 'added', null, $info['sha256'], 0, $info['size'], 'File baru terdeteksi']);
                $this->results['changes'][] = ['file' => $path, 'type' => 'added'];
            }
        }

        // Check for deleted files
        foreach ($baselineMap as $path => $hash) {
            if (!isset($currentFiles[$path])) {
                $this->results['deleted']++;
                $db->prepare("UPDATE file_checksums SET status = 'deleted' WHERE website_id = ? AND file_path = ?")->execute([$this->websiteId, $path]);
                $changeInsert->execute([$this->websiteId, $path, 'deleted', $hash, null, 0, 0, 'File telah dihapus']);
                $this->results['changes'][] = ['file' => $path, 'type' => 'deleted', 'old_hash' => $hash];
            }
        }

        return $this->results;
    }

    private function scanFiles(): array
    {
        $files = [];
        $this->scanDir($this->basePath, '', $files);
        ksort($files);
        return $files;
    }

    private function scanDir(string $fullPath, string $relPath, array &$files): void
    {
        if (!is_dir($fullPath)) return;
        $items = new \FilesystemIterator($fullPath, \FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            $name = $item->getFilename();
            $rel = $relPath ? $relPath . '/' . $name : $name;

            if ($item->isDir()) {
                if (in_array($name, $this->excludeDirs) || str_starts_with($name, '.')) continue;
                $fileCount = 0;
                $this->scanDir($item->getPathname(), $rel, $files);
                continue;
            }

            $ext = strtolower($item->getExtension());
            if (!in_array($ext, $this->includeExts)) continue;

            $filepath = $item->getPathname();
            if ($item->getSize() > 5 * 1024 * 1024) continue; // skip files > 5MB

            $hash = hash_file('sha256', $filepath);
            $mtime = date('Y-m-d H:i:s', $item->getMTime());

            $files[$rel] = [
                'sha256' => $hash,
                'size' => $item->getSize(),
                'mtime' => $mtime,
            ];
        }
    }

    private function generateDiff(string $filepath, string $oldHash): string
    {
        $content = file_get_contents($filepath);
        if ($content === false || strlen($content) > 50000) return '[File terlalu besar untuk diff]';

        return substr($content, 0, 2000);
    }
}
