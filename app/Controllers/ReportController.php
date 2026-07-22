<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\Website;
use App\Models\User;
use App\Models\MonitorLog;

class ReportController
{
    public function index(): string
    {
        Auth::check();
        $period = $_GET['period'] ?? 'daily';
        $date = $_GET['date'] ?? date('Y-m-d');

        $summary = Website::summary();
        $websites = Website::withLatestStatus();
        $activities = User::recentActivity(20);

        // Get monitor stats for period
        $db = \App\Config\Database::getConnection();
        $periodStart = match($period) {
            'weekly' => date('Y-m-d', strtotime('-7 days')),
            'monthly' => date('Y-m-d', strtotime('-30 days')),
            'yearly' => date('Y-m-d', strtotime('-365 days')),
            default => $date,
        };

        $totalChecks = $db->prepare("SELECT COUNT(*) as c FROM monitor_logs WHERE checked_at >= ?");
        $totalChecks->execute([$periodStart]);
        $totalChecks = $totalChecks->fetch()['c'];

        $avgResponse = $db->prepare("SELECT COALESCE(AVG(response_time_ms),0) as avg FROM monitor_logs WHERE checked_at >= ?");
        $avgResponse->execute([$periodStart]);
        $avgResponse = round((float)$avgResponse->fetch()['avg']);

        $incidents = $db->prepare("SELECT COUNT(*) as c FROM incidents WHERE created_at >= ?");
        $incidents->execute([$periodStart]);
        $incidents = $incidents->fetch()['c'];

        return view('reports.index', [
            'period' => $period,
            'date' => $date,
            'summary' => $summary,
            'websites' => $websites,
            'activities' => $activities,
            'totalChecks' => $totalChecks,
            'avgResponse' => $avgResponse,
            'incidents' => $incidents,
            'pageTitle' => 'Laporan',
        ]);
    }

    public function exportCsv(): void
    {
        Auth::check();
        $period = $_GET['period'] ?? 'daily';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="webguardian-report-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Website', 'URL', 'Status', 'Response Time (ms)', 'Skor Keamanan', 'Last Check']);

        $websites = Website::withLatestStatus();
        foreach ($websites as $w) {
            fputcsv($output, [
                $w['nama_website'],
                $w['url'],
                ($w['is_up'] ?? 0) ? 'Online' : 'Offline',
                $w['response_time_ms'] ?? '-',
                $w['security_score'] ?? '0',
                $w['last_check'] ?? '-',
            ]);
        }
        fclose($output);
        exit;
    }

    public function notificationHistory(): string
    {
        Auth::check();
        $notifications = \App\Models\Notification::unread(50);
        return view('notifications.index', [
            'notifications' => $notifications,
            'pageTitle' => 'Notifikasi',
        ]);
    }

    public function markRead(): void
    {
        Auth::check();
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            \App\Models\Notification::markRead($id);
        }
        jsonResponse(['success' => true]);
    }

    public function markAllRead(): void
    {
        Auth::check();
        \App\Models\Notification::markAllRead();
        jsonResponse(['success' => true]);
    }
}
