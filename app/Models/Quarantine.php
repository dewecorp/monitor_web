<?php
declare(strict_types=1);

namespace App\Models;

class Quarantine extends Model
{
    protected static string $table = 'quarantine';

    public static function allItems(int $limit = 50): array
    {
        return static::db()->query("
            SELECT q.*, w.nama_website, u.nama as user_name
            FROM quarantine q
            LEFT JOIN websites w ON w.id = q.website_id
            LEFT JOIN users u ON u.id = q.quarantined_by
            ORDER BY q.quarantined_at DESC
            LIMIT {$limit}
        ")->fetchAll();
    }

    public static function active(): array
    {
        return static::db()->query("
            SELECT q.*, w.nama_website
            FROM quarantine q
            LEFT JOIN websites w ON w.id = q.website_id
            WHERE q.status = 'quarantined'
            ORDER BY q.quarantined_at DESC
        ")->fetchAll();
    }

    public static function countActive(): int
    {
        return (int)static::db()->query("SELECT COUNT(*) as c FROM quarantine WHERE status='quarantined'")->fetch()['c'];
    }

    public static function quarantineFile(string $originalPath, string $reason, string $severity = 'high', ?int $websiteId = null, bool $auto = false): bool
    {
        if (!file_exists($originalPath)) return false;

        $dir = STORAGE_PATH . '/quarantine/' . date('Y-m-d');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = basename($originalPath);
        $dest = $dir . '/' . date('His') . '_' . $filename;
        $hash = hash_file('sha256', $originalPath);
        $size = filesize($originalPath);

        if (!rename($originalPath, $dest)) return false;

        file_put_contents($dest . '.meta', json_encode([
            'original' => $originalPath,
            'size' => $size,
            'hash' => $hash,
            'reason' => $reason,
            'severity' => $severity,
            'quarantined_at' => date('Y-m-d H:i:s'),
        ]));

        static::insert([
            'website_id' => $websiteId,
            'original_path' => $originalPath,
            'file_name' => $filename,
            'file_size' => $size,
            'sha256' => $hash,
            'reason' => $reason,
            'severity' => $severity,
            'auto_quarantine' => $auto ? 1 : 0,
            'quarantined_by' => $_SESSION['user_id'] ?? null,
        ]);

        return true;
    }

    public static function restore(int $id): bool
    {
        $item = static::find($id);
        if (!$item || $item['status'] !== 'quarantined') return false;

        $dir = STORAGE_PATH . '/quarantine/' . date('Y-m-d', strtotime($item['quarantined_at']));
        $filename = date('His', strtotime($item['quarantined_at'])) . '_' . $item['file_name'];
        $source = $dir . '/' . $filename;

        if (!file_exists($source)) {
            $files = glob(dirname($dir) . '/[0-9]*_' . $item['file_name']);
            $source = $files[0] ?? '';
            if (!$source || !file_exists($source)) return false;
        }

        if (!copy($source, $item['original_path'])) return false;

        static::update($id, ['status' => 'restored', 'restored_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    public static function deletePermanently(int $id): bool
    {
        $item = static::find($id);
        if (!$item) return false;

        $dir = STORAGE_PATH . '/quarantine/' . date('Y-m-d', strtotime($item['quarantined_at']));
        $filename = date('His', strtotime($item['quarantined_at'])) . '_' . $item['file_name'];
        $source = $dir . '/' . $filename;

        if (file_exists($source)) unlink($source);
        $meta = $source . '.meta';
        if (file_exists($meta)) unlink($meta);

        static::update($id, ['status' => 'deleted']);
        return true;
    }
}
