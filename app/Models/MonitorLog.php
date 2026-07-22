<?php
declare(strict_types=1);

namespace App\Models;

class MonitorLog extends Model
{
    protected static string $table = 'monitor_logs';

    public static function latestForWebsite(int $websiteId): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM monitor_logs WHERE website_id = ? ORDER BY checked_at DESC LIMIT 1");
        $stmt->execute([$websiteId]);
        return $stmt->fetch() ?: null;
    }

    public static function history(int $websiteId, int $limit = 30): array
    {
        $stmt = static::db()->prepare("SELECT * FROM monitor_logs WHERE website_id = ? ORDER BY checked_at DESC LIMIT ?");
        $stmt->execute([$websiteId, $limit]);
        return $stmt->fetchAll();
    }

    public static function dailyUptime(int $websiteId, int $days = 30): array
    {
        $stmt = static::db()->prepare("
            SELECT DATE(checked_at) as date,
                COUNT(*) as checks,
                SUM(is_up) as up_count,
                ROUND((SUM(is_up) / COUNT(*)) * 100, 2) as uptime_pct
            FROM monitor_logs
            WHERE website_id = ? AND checked_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(checked_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$websiteId, $days]);
        return $stmt->fetchAll();
    }
}
