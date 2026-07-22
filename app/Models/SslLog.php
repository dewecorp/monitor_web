<?php
declare(strict_types=1);

namespace App\Models;

class SslLog extends Model
{
    protected static string $table = 'ssl_logs';

    public static function latestForWebsite(int $websiteId): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM ssl_logs WHERE website_id = ? ORDER BY checked_at DESC LIMIT 1");
        $stmt->execute([$websiteId]);
        return $stmt->fetch() ?: null;
    }

    public static function expiringSoon(int $days = 30): array
    {
        return static::db()->query("
            SELECT s.*, w.nama_website, w.url
            FROM ssl_logs s
            JOIN websites w ON w.id = s.website_id
            WHERE s.checked_at = (SELECT MAX(s2.checked_at) FROM ssl_logs s2 WHERE s2.website_id = s.website_id)
                AND s.ssl_remaining_days <= {$days}
                AND s.ssl_valid = 1
            ORDER BY s.ssl_remaining_days ASC
        ")->fetchAll();
    }
}
