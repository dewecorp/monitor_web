<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\Website;
use App\Models\User;
use App\Models\MonitorLog;

class DashboardController
{
    public function index(): string
    {
        Auth::check();

        $summary = Website::summary();
        $websites = Website::withLatestStatus();
        $activities = User::recentActivity(8);
        $trafficData = \App\Models\TrafficLog::allWebsitesSummary(7);

        return view('dashboard.index', [
            'summary' => $summary,
            'websites' => $websites,
            'activities' => $activities,
            'trafficData' => $trafficData,
            'pageTitle' => 'Dashboard',
        ]);
    }

    public function apiChartData(): void
    {
        Auth::check();
        $db = \App\Config\Database::getConnection();
        $websiteId = (int)($_GET['website_id'] ?? 0);

        // Uptime per day for last 7 days
        $uptimeData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(is_up) as up FROM monitor_logs WHERE website_id = ? AND DATE(checked_at) = ?");
            $stmt->execute([$websiteId > 0 ? $websiteId : 1, $date]);
            $row = $stmt->fetch();
            $uptimeData[] = [
                'date' => date('d M', strtotime($date)),
                'uptime' => $row && $row['total'] > 0 ? round(($row['up'] / $row['total']) * 100, 1) : 100,
            ];
        }

        // Response time for last 24 hours
        $respStmt = $db->query("
            SELECT DATE_FORMAT(checked_at, '%H:00') as hour,
                   ROUND(AVG(response_time_ms)) as avg_resp
            FROM monitor_logs
            WHERE checked_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY hour
            ORDER BY MIN(checked_at) ASC
            LIMIT 24
        ");
        $responseData = $respStmt->fetchAll();

        // Security scores for all websites
        $secStmt = $db->query("
            SELECT w.nama_website, COALESCE(s.score, 0) as score
            FROM websites w
            LEFT JOIN security_logs s ON s.website_id = w.id
                AND s.checked_at = (SELECT MAX(s2.checked_at) FROM security_logs s2 WHERE s2.website_id = w.id)
            WHERE w.status='active'
            ORDER BY w.nama_website ASC
        ");
        $securityData = $secStmt->fetchAll();

        // Activity trend for last 7 days
        $activityStmt = $db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM activity_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $activityData = $activityStmt->fetchAll();

        jsonResponse([
            'uptime' => $uptimeData,
            'response_time' => $responseData,
            'security' => $securityData,
            'activity' => $activityData,
        ]);
    }
}
