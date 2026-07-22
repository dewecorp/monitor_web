<?php
declare(strict_types=1);

namespace App\Services;

class AiAnalysis
{
    private int $websiteId;
    private array $data = [];
    private array $report = [
        'summary' => '',
        'score' => 0,
        'grade' => 'F',
        'findings' => [],
        'recommendations' => [],
        'trends' => [],
        'generated_at' => '',
        'verdict' => '',
    ];

    public function __construct(int $websiteId)
    {
        $this->websiteId = $websiteId;
    }

    public function analyze(): array
    {
        $this->data['website'] = $this->fetchWebsite();
        if (!$this->data['website']) return $this->report;

        $this->data['health'] = $this->fetchLatest('monitor_logs', 'is_up, response_time_ms, status_code');
        $this->data['security'] = $this->fetchLatest('security_logs', 'score, headers_secure, has_hsts, has_csp, has_xss_protection');
        $this->data['ssl'] = $this->fetchLatest('ssl_logs', 'ssl_remaining_days, ssl_issuer, ssl_valid');
        $this->data['agent'] = $this->fetchLatest('agent_reports', 'cpu_usage, memory_usage, disk_usage');
        $this->data['incidents'] = $this->countOpenIncidents();
        $this->data['file_changes'] = $this->countUnreviewedChanges();
        $this->data['threat_logs'] = $this->fetchThreats();

        $this->calculateScore();
        $this->generateFindings();
        $this->generateRecommendations();
        $this->generateSummary();
        $this->generateVerdict();
        $this->detectTrends();

        $this->report['generated_at'] = date('Y-m-d H:i:s');
        return $this->report;
    }

    private function fetchWebsite(): ?array
    {
        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("SELECT id, nama_website, url, domain_expired FROM websites WHERE id = ?");
        $stmt->execute([$this->websiteId]);
        return $stmt->fetch() ?: null;
    }

    private function fetchLatest(string $table, string $columns): ?array
    {
        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("SELECT {$columns} FROM {$table} WHERE website_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$this->websiteId]);
        return $stmt->fetch() ?: null;
    }

    private function countOpenIncidents(): int
    {
        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as c FROM incidents WHERE website_id = ? AND status IN ('open','investigating')");
        $stmt->execute([$this->websiteId]);
        return (int)$stmt->fetch()['c'];
    }

    private function countUnreviewedChanges(): int
    {
        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as c FROM file_changes WHERE website_id = ? AND is_reviewed = 0");
        $stmt->execute([$this->websiteId]);
        return (int)$stmt->fetch()['c'];
    }

    private function fetchThreats(): array
    {
        return []; // Placeholder for threat detection integration
    }

    private function calculateScore(): void
    {
        $s = 100;
        $h = $this->data['health'];
        $sec = $this->data['security'];
        $ssl = $this->data['ssl'];
        $agent = $this->data['agent'];

        if ($h) {
            if (!$h['is_up']) { $s -= 30; } elseif ($h['response_time_ms'] > 2000) { $s -= 10; }
        }
        if ($sec) {
            $s -= (100 - (int)$sec['score']) * 0.3;
            if (!$sec['has_hsts']) $s -= 5;
            if (!$sec['has_csp']) $s -= 5;
        }
        if ($ssl) {
            if (!$ssl['ssl_valid']) { $s -= 20; }
            elseif (($ssl['ssl_remaining_days'] ?? 999) < 30) { $s -= 15; }
        }
        if ($agent) {
            if (($agent['cpu_usage'] ?? 0) > 90) $s -= 10;
            if (($agent['memory_usage'] ?? 0) > 90) $s -= 10;
            if (($agent['disk_usage'] ?? 0) > 90) $s -= 10;
        }
        $s -= $this->data['incidents'] * 5;
        $s -= $this->data['file_changes'] * 3;

        $this->report['score'] = max(0, (int)$s);
        $this->report['grade'] = $s >= 90 ? 'A' : ($s >= 75 ? 'B' : ($s >= 60 ? 'C' : ($s >= 40 ? 'D' : 'F')));
    }

    private function generateFindings(): void
    {
        $f = [];
        $h = $this->data['health'];
        $sec = $this->data['security'];
        $ssl = $this->data['ssl'];

        if ($h) {
            $f[] = $h['is_up']
                ? "✅ Website online (HTTP {$h['status_code']}, {$h['response_time_ms']}ms)"
                : "❌ Website DOWN ({$h['status_code']}, {$h['response_time_ms']}ms)";
        }
        if ($sec) {
            $seclabel = $sec['score'] >= 80 ? 'baik' : ($sec['score'] >= 50 ? 'sedang' : 'rendah');
            $f[] = "🛡️ Security score: {$sec['score']}% ($seclabel)";
            if ($sec['has_hsts']) $f[] = "🔒 HSTS aktif";
            if (!$sec['has_csp']) $f[] = "⚠️ CSP tidak terdeteksi";
        }
        if ($ssl) {
            $sslLabel = $ssl['ssl_valid'] ? "valid, sisa {$ssl['ssl_remaining_days']} hari" : 'TIDAK VALID';
            $f[] = "🔐 SSL: $sslLabel";
        }
        if ($this->data['incidents'] > 0) {
            $f[] = "🚨 {$this->data['incidents']} insiden terbuka";
        }
        if ($this->data['file_changes'] > 0) {
            $f[] = "📁 {$this->data['file_changes']} perubahan file belum direview";
        }
        if ($this->data['agent']) {
            $a = $this->data['agent'];
            $f[] = "🖥️ Server: CPU {$a['cpu_usage']}% | RAM {$a['memory_usage']}% | Disk {$a['disk_usage']}%";
        }

        $this->report['findings'] = $f;
    }

    private function generateRecommendations(): void
    {
        $r = [];
        $h = $this->data['health'];
        $sec = $this->data['security'];
        $ssl = $this->data['ssl'];
        $agent = $this->data['agent'];

        if ($h && !$h['is_up']) {
            $r[] = ['priority' => 'KRITIS', 'text' => 'Segera periksa penyebab website down. Cek server, DNS, dan firewall.'];
        }
        if ($ssl && $ssl['ssl_valid'] && ($ssl['ssl_remaining_days'] ?? 999) < 30) {
            $r[] = ['priority' => 'TINGGI', 'text' => "SSL certificate akan kadaluarsa dalam {$ssl['ssl_remaining_days']} hari. Segera perbarui."];
        }
        if ($sec && !$sec['has_hsts']) {
            $r[] = ['priority' => 'SEDANG', 'text' => 'Aktifkan HSTS untuk mencegah SSL stripping. Tambahkan header Strict-Transport-Security.'];
        }
        if ($sec && !$sec['has_csp']) {
            $r[] = ['priority' => 'SEDANG', 'text' => 'Implementasikan Content-Security-Policy untuk mencegah XSS dan data injection.'];
        }
        if ($this->data['incidents'] > 0) {
            $r[] = ['priority' => 'TINGGI', 'text' => "Review dan resolve {$this->data['incidents']} insiden terbuka."];
        }
        if ($this->data['file_changes'] > 0) {
            $r[] = ['priority' => 'SEDANG', 'text' => "Review {$this->data['file_changes']} perubahan file yang belum diverifikasi."];
        }
        if ($agent) {
            if (($agent['cpu_usage'] ?? 0) > 85) {
                $r[] = ['priority' => 'TINGGI', 'text' => "CPU usage {$agent['cpu_usage']}% — overload. Cek proses dan optimalkan."];
            }
            if (($agent['disk_usage'] ?? 0) > 85) {
                $r[] = ['priority' => 'TINGGI', 'text' => "Disk {$agent['disk_usage']}% hampir penuh. Bersihkan file tidak perlu."];
            }
            if (($agent['memory_usage'] ?? 0) > 85) {
                $r[] = ['priority' => 'SEDANG', 'text' => "RAM usage {$agent['memory_usage']}% — restart PHP-FPM atau upgrade RAM."];
            }
        }

        if (empty($r)) {
            $r[] = ['priority' => 'INFO', 'text' => 'Website dalam kondisi baik. Tidak ada rekomendasi khusus.'];
        }

        $this->report['recommendations'] = $r;
    }

    private function generateSummary(): void
    {
        $s = $this->report['score'];
        $name = $this->data['website']['nama_website'] ?? 'Website';

        if ($s >= 90) {
            $this->report['summary'] = "{$name} dalam kondisi sangat baik. Keamanan terjaga, performa optimal, tidak ditemukan masalah signifikan.";
        } elseif ($s >= 75) {
            $this->report['summary'] = "{$name} dalam kondisi baik. Beberapa aspek perlu perhatian ringan untuk mencapai keamanan optimal.";
        } elseif ($s >= 60) {
            $this->report['summary'] = "{$name} cukup aman namun ada beberapa kerentanan yang perlu ditangani. Prioritaskan rekomendasi tingkat tinggi.";
        } elseif ($s >= 40) {
            $this->report['summary'] = "{$name} memiliki beberapa masalah keamanan yang perlu ditangani segera. Risiko sedang hingga tinggi terdeteksi.";
        } else {
            $this->report['summary'] = "⚠️ {$name} dalam kondisi kritis. Tindakan segera diperlukan untuk mencegah kerusakan lebih lanjut.";
        }
    }

    private function generateVerdict(): void
    {
        $s = $this->report['score'];
        $this->report['verdict'] = match(true) {
            $s >= 90 => 'Excellent',
            $s >= 75 => 'Good',
            $s >= 60 => 'Fair',
            $s >= 40 => 'Poor',
            default => 'Critical',
        };
    }

    private function detectTrends(): void
    {
        $db = \App\Config\Database::getConnection();
        $trends = [];

        // Response time trend (last 7 days)
        $respStmt = $db->prepare("SELECT DATE(checked_at) as date, AVG(response_time_ms) as avg_resp FROM monitor_logs WHERE website_id = ? AND checked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(checked_at) ORDER BY date ASC");
        $respStmt->execute([$this->websiteId]);
        $respData = $respStmt->fetchAll();

        if (count($respData) >= 2) {
            $first = (float)$respData[0]['avg_resp'];
            $last = (float)$respData[count($respData) - 1]['avg_resp'];
            $diff = $last - $first;
            $dir = $diff > 50 ? 'meningkat' : ($diff < -50 ? 'menurun' : 'stabil');
            $trends[] = "Response time {$dir} (" . round($first) . "ms → " . round($last) . "ms)";
        }

        // Security score trend (last 5 checks)
        $secStmt = $db->prepare("SELECT score, checked_at FROM security_logs WHERE website_id = ? ORDER BY checked_at DESC LIMIT 5");
        $secStmt->execute([$this->websiteId]);
        $secData = $secStmt->fetchAll();
        if (count($secData) >= 2) {
            $trend = (int)$secData[0]['score'] - (int)$secData[count($secData) - 1]['score'];
            $trends[] = "Security score " . ($trend > 0 ? "meningkat +{$trend}" : ($trend < 0 ? "menurun {$trend}" : "stabil")) . " ({$secData[0]['score']}%)";
        }

        $this->report['trends'] = $trends;
    }
}
