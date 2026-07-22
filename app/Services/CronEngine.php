<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Website;
use App\Models\Incident;
use App\Models\Notification;
use App\Models\SslLog;
use App\Models\Setting;

class CronEngine
{
    private MonitorService $monitor;
    private array $settings = [];
    private array $results = [
        'checked' => 0, 'errors' => 0,
        'incidents_created' => 0, 'incidents_resolved' => 0,
        'notifications_sent' => 0,
        'details' => [],
    ];

    public function __construct()
    {
        $this->monitor = new MonitorService();
        $this->loadSettings();
    }

    private function loadSettings(): void
    {
        $rows = Setting::all();
        foreach ($rows as $row) {
            $this->settings[$row['key']] = $row['value'];
        }
    }

    public function run(): array
    {
        $websites = Website::active();

        foreach ($websites as $website) {
            $this->checkWebsite((int)$website['id'], $website['url'], $website['nama_website']);
        }

        return $this->results;
    }

    private function checkWebsite(int $id, string $url, string $name): void
    {
        $health = $this->monitor->checkWebsite($id, $url);

        if ($health['is_up']) {
            $this->results['checked']++;
            $resolved = Incident::resolve($id, "Website Down: {$name}");
            if ($resolved) $this->results['incidents_resolved']++;
        } else {
            $this->results['errors']++;
            $this->handleWebsiteDown($id, $name, $url, $health);
        }

        // SSL check
        $this->checkSSL($id, $url, $name);

        // Domain check
        $this->checkDomain($id, $url, $name);

        $this->results['details'][] = [
            'website' => $name,
            'status' => $health['is_up'] ? 'OK' : 'DOWN',
            'http_code' => $health['status_code'],
            'response_ms' => $health['response_time_ms'],
        ];
    }

    private function handleWebsiteDown(int $id, string $name, string $url, array $health): void
    {
        $downAfter = (int)($this->settings['notify_down_after'] ?? 2);
        $consecutiveDowns = $this->countConsecutiveDowns($id);

        if ($consecutiveDowns >= $downAfter) {
            $incidentId = Incident::createIfNotOpen(
                $id,
                "Website Down: {$name}",
                "Website {$name} ({$url}) tidak bisa dijangkau. HTTP {$health['status_code']}, Response {$health['response_time_ms']}ms. Down selama {$consecutiveDowns} menit.",
                'critical'
            );

            if ($incidentId) {
                $this->results['incidents_created']++;
                Notification::create($id, 'website_down', 'internal', "Website Down: {$name}", "{$name} telah down selama {$consecutiveDowns} menit.");

                if (($this->settings['telegram_enabled'] ?? '0') === '1') {
                    Notification::sendTelegram("🚨 <b>DOWN:</b> {$name}\n{$url}\nHTTP: {$health['status_code']}\nResponse: {$health['response_time_ms']}ms");
                    $this->results['notifications_sent']++;
                }
                if (($this->settings['discord_enabled'] ?? '0') === '1') {
                    Notification::sendDiscord("🚨 **DOWN:** {$name}\n{$url}\nHTTP: {$health['status_code']}\nResponse: {$health['response_time_ms']}ms");
                    $this->results['notifications_sent']++;
                }
            }
        }
    }

    private function checkSSL(int $id, string $url, string $name): void
    {
        $sslData = SslLog::latestForWebsite($id);
        if (!$sslData || !$sslData['ssl_valid']) return;

        $warningDays = (int)($this->settings['ssl_warning_days'] ?? 30);
        $remaining = (int)($sslData['ssl_remaining_days'] ?? 365);

        if ($remaining <= $warningDays) {
            $incidentId = Incident::createIfNotOpen(
                $id,
                "SSL Expiring: {$name}",
                "SSL certificate untuk {$name} akan kadaluarsa dalam {$remaining} hari.",
                $remaining <= 7 ? 'critical' : 'high'
            );

            if ($incidentId) {
                $this->results['incidents_created']++;
                Notification::create($id, 'ssl_expiring', 'internal', "SSL Expiring: {$name}", "Sisa {$remaining} hari.");
            }
        }
    }

    private function checkDomain(int $id, string $url, string $name): void
    {
        $website = Website::find($id);
        if (!$website || !$website['domain_expired']) return;

        $remaining = max(0, (int)((strtotime($website['domain_expired']) - time()) / 86400));
        $warningDays = (int)($this->settings['domain_warning_days'] ?? 30);

        if ($remaining <= $warningDays) {
            $incidentId = Incident::createIfNotOpen(
                $id,
                "Domain Expiring: {$name}",
                "Domain untuk {$name} akan kadaluarsa dalam {$remaining} hari.",
                $remaining <= 7 ? 'critical' : 'high'
            );

            if ($incidentId) {
                $this->results['incidents_created']++;
                Notification::create($id, 'domain_expiring', 'internal', "Domain Expiring: {$name}", "Sisa {$remaining} hari.");
            }
        }
    }

    private function countConsecutiveDowns(int $websiteId): int
    {
        $stmt = \App\Config\Database::getConnection()->prepare(
            "SELECT COUNT(*) as c FROM monitor_logs WHERE website_id = ? AND is_up = 0 AND checked_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
        );
        $stmt->execute([$websiteId]);
        return (int)$stmt->fetch()['c'];
    }
}
