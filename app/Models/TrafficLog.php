<?php
declare(strict_types=1);

namespace App\Models;

class TrafficLog extends Model
{
    protected static string $table = 'traffic_logs';

    public static function summary(int $websiteId, int $days = 7): array
    {
        $stmt = static::db()->prepare("
            SELECT COALESCE(SUM(visitors),0) as total_visitors,
                COALESCE(SUM(page_views),0) as total_views,
                COALESCE(SUM(bandwidth_mb),0) as total_bandwidth,
                COALESCE(AVG(avg_response_ms),0) as avg_response
            FROM traffic_logs
            WHERE website_id = ? AND logged_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ");
        $stmt->execute([$websiteId, $days]);
        return $stmt->fetch();
    }

    public static function chart(int $websiteId, int $days = 7): array
    {
        $stmt = static::db()->prepare("
            SELECT logged_date, visitors, page_views, bandwidth_mb, avg_response_ms
            FROM traffic_logs
            WHERE website_id = ? AND logged_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ORDER BY logged_date ASC
        ");
        $stmt->execute([$websiteId, $days]);
        return $stmt->fetchAll();
    }

    public static function record(int $websiteId, int $visitors, int $pageViews, float $bandwidth, float $avgResponse): void
    {
        $stmt = static::db()->prepare("
            INSERT INTO traffic_logs (website_id, visitors, page_views, bandwidth_mb, avg_response_ms, logged_date)
            VALUES (?, ?, ?, ?, ?, CURDATE())
            ON DUPLICATE KEY UPDATE
                visitors = visitors + ?,
                page_views = page_views + ?,
                bandwidth_mb = bandwidth_mb + ?,
                avg_response_ms = (avg_response_ms + ?) / 2
        ");
        $stmt->execute([$websiteId, $visitors, $pageViews, $bandwidth, $avgResponse,
                        $visitors, $pageViews, $bandwidth, $avgResponse]);
    }

    public static function allWebsitesSummary(int $days = 7): array
    {
        return static::db()->query("
            SELECT w.id, w.nama_website, w.url,
                COALESCE(SUM(t.visitors),0) as visitors,
                COALESCE(SUM(t.page_views),0) as page_views,
                COALESCE(SUM(t.bandwidth_mb),0) as bandwidth,
                COALESCE(AVG(t.avg_response_ms),0) as avg_response
            FROM websites w
            LEFT JOIN traffic_logs t ON t.website_id = w.id
                AND t.logged_date >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)
            WHERE w.status='active'
            GROUP BY w.id
            ORDER BY visitors DESC
        ")->fetchAll();
    }
}
