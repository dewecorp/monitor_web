<?php
declare(strict_types=1);

namespace App\Models;

class Website extends Model
{
    protected static string $table = 'websites';
    protected static string $primaryKey = 'id';

    public static function active(): array
    {
        return static::where('status', 'active');
    }

    public static function withLatestStatus(): array
    {
        return static::db()->query("
            SELECT w.*,
                m.is_up, m.response_time_ms, m.status_code, m.checked_at as last_check,
                s.score as security_score, s.checked_at as last_scan
            FROM websites w
            LEFT JOIN monitor_logs m ON m.website_id = w.id
                AND m.checked_at = (SELECT MAX(m2.checked_at) FROM monitor_logs m2 WHERE m2.website_id = w.id)
            LEFT JOIN security_logs s ON s.website_id = w.id
                AND s.checked_at = (SELECT MAX(s2.checked_at) FROM security_logs s2 WHERE s2.website_id = w.id)
            WHERE w.status = 'active'
            ORDER BY w.nama_website ASC
        ")->fetchAll();
    }

    public static function summary(): array
    {
        $total = static::db()->query("SELECT COUNT(*) as c FROM websites WHERE status='active'")->fetch()['c'];
        $online = static::db()->query("
            SELECT COUNT(DISTINCT w.id) as c FROM websites w
            JOIN monitor_logs m ON m.website_id = w.id
                AND m.checked_at = (SELECT MAX(m2.checked_at) FROM monitor_logs m2 WHERE m2.website_id = w.id)
            WHERE w.status='active' AND m.is_up = 1
        ")->fetch()['c'];
        $offline = $total - $online;

        $avgResponse = static::db()->query("
            SELECT COALESCE(AVG(m.response_time_ms),0) as avg
            FROM monitor_logs m WHERE m.checked_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ")->fetch()['avg'];

        $avgSecurity = static::db()->query("
            SELECT COALESCE(AVG(s.score),0) as avg
            FROM security_logs s
            WHERE s.checked_at = (SELECT MAX(s2.checked_at) FROM security_logs s2 WHERE s2.website_id = s.website_id)
        ")->fetch()['avg'];

        return [
            'total' => $total,
            'online' => $online,
            'offline' => $offline,
            'avg_response' => round((float)$avgResponse),
            'avg_security' => round((float)$avgSecurity),
        ];
    }

    public static function statsByCategory(): array
    {
        return static::db()->query("
            SELECT kategori, COUNT(*) as total,
                SUM(CASE WHEN m.is_up = 1 THEN 1 ELSE 0 END) as online
            FROM websites w
            LEFT JOIN monitor_logs m ON m.website_id = w.id
                AND m.checked_at = (SELECT MAX(checked_at) FROM monitor_logs WHERE website_id = w.id)
            WHERE w.status='active'
            GROUP BY w.kategori
        ")->fetchAll();
    }
}
