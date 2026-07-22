<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\Website;
use App\Models\MonitorLog;
use App\Models\User;
use App\Services\MonitorService;

class MonitorController
{
    public function health(): string
    {
        Auth::check();
        $websites = Website::withLatestStatus();
        return view('monitor.health', ['websites' => $websites, 'pageTitle' => 'Kesehatan']);
    }

    public function healthDetail(array $params): string
    {
        Auth::check();
        $website = Website::find((int)$params['id']);
        if (!$website) abort(404);
        $history = MonitorLog::history((int)$params['id'], 30);
        return view('monitor.health_detail', [
            'website' => $website,
            'history' => $history,
            'pageTitle' => $website['nama_website'],
        ]);
    }

    public function traffic(): string
    {
        Auth::check();
        $websites = Website::active();
        $trafficSummary = \App\Models\TrafficLog::allWebsitesSummary(7);
        return view('monitor.traffic', [
            'websites' => $websites,
            'trafficSummary' => $trafficSummary,
            'pageTitle' => 'Traffic',
        ]);
    }

    public function trafficDetail(array $params): string
    {
        Auth::check();
        $website = Website::find((int)$params['id']);
        if (!$website) abort(404);
        $days = (int)($_GET['days'] ?? 7);
        $summary = \App\Models\TrafficLog::summary((int)$params['id'], $days);
        $chart = \App\Models\TrafficLog::chart((int)$params['id'], $days);
        return view('monitor.traffic_detail', [
            'website' => $website,
            'summary' => $summary,
            'chart' => $chart,
            'days' => $days,
            'pageTitle' => 'Traffic - ' . $website['nama_website'],
        ]);
    }

    public function apiCheckAll(): void
    {
        Auth::check();
        $service = new MonitorService();
        $result = $service->checkAll();
        User::logActivity($_SESSION['user_id'], 'Check All',
            "Memeriksa semua website: {$result['checked']} online, {$result['errors']} offline");
        jsonResponse([
            'success' => true,
            'message' => "Selesai! {$result['checked']} online, {$result['errors']} offline.",
        ]);
    }

    public function apiCheckSingle(array $params): void
    {
        Auth::check();
        $website = Website::find((int)$params['id']);
        if (!$website) {
            jsonResponse(['success' => false, 'message' => 'Website tidak ditemukan'], 404);
        }
        $service = new MonitorService();
        $health = $service->checkWebsite((int)$website['id'], $website['url']);
        User::logActivity($_SESSION['user_id'], 'Check Website',
            "Check: {$website['nama_website']} - {$health['status_code']}, {$health['response_time_ms']}ms");
        jsonResponse([
            'success' => true,
            'message' => "{$website['nama_website']}: {$health['status_code']}, {$health['response_time_ms']}ms",
            'health' => $health,
        ]);
    }

    public function apiTrafficData(array $params): void
    {
        Auth::check();
        $website = Website::find((int)$params['id']);
        if (!$website) {
            jsonResponse(['success' => false, 'message' => 'Website tidak ditemukan'], 404);
        }
        $days = (int)($_GET['days'] ?? 7);
        $chart = \App\Models\TrafficLog::chart((int)$params['id'], $days);
        jsonResponse(['success' => true, 'data' => $chart]);
    }
}
