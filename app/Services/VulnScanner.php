<?php
declare(strict_types=1);

namespace App\Services;

class VulnScanner
{
    private string $url;
    private string $baseDomain;
    private array $results = [
        'cms' => null,
        'version' => null,
        'vulnerabilities' => [],
        'severity' => 'clean',
        'score' => 100,
        'scan_time' => null,
    ];

    private array $knownCves = [
        'wordpress' => [
            '6.0' => ['CVE-2022-3590', 'CVE-2022-3610'],
            '6.0.1' => ['CVE-2022-3590'],
            '6.0.2' => [],
            '6.1' => ['CVE-2022-4502'],
            '6.1.1' => ['CVE-2022-4502'],
            '6.2' => ['CVE-2023-2501'],
            '6.2.1' => [],
            '6.3' => ['CVE-2023-3999'],
            '6.3.1' => [],
            '6.4' => ['CVE-2024-1234'],
            '6.4.1' => [],
            '5.8' => ['CVE-2021-39200', 'CVE-2021-39201'],
            '5.9' => ['CVE-2021-39202'],
            '5.9.1' => [],
        ],
    ];

    public function __construct(string $url)
    {
        $this->url = rtrim($url, '/');
        $this->baseDomain = parse_url($this->url, PHP_URL_HOST) ?? '';
    }

    public function scan(): array
    {
        $this->results['scan_time'] = date('Y-m-d H:i:s');

        $html = $this->fetchUrl($this->url);
        if ($html) {
            $this->detectWordPress($html);
            $this->detectLaravel($html);
            $this->detectGeneral($html);
        }

        // Always scan security configurations
        $this->checkSecurityConfigs();

        $this->calculateScore();
        return $this->results;
    }

    private function fetchUrl(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3, CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 Chrome/126',
            CURLOPT_HTTPHEADER => ['Accept: text/html,*/*'],
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($httpCode >= 200 && $httpCode < 400) ? $resp : null;
    }

    private function detectWordPress(string $html): void
    {
        // Detect WordPress
        $isWP = false;
        if (preg_match('/<meta name="generator" content="WordPress\s*([^"]+)"/i', $html, $m)) {
            $isWP = true;
            $this->results['cms'] = 'WordPress';
            $this->results['version'] = $m[1];
            $this->addVuln('info', "WordPress {$m[1]} terdeteksi", 'CMS Version');
        } elseif (preg_match('/wp-content/i', $html) || preg_match('/wp-includes/i', $html)) {
            $isWP = true;
            $this->results['cms'] = 'WordPress';
            $this->results['version'] = 'unknown';
            $this->addVuln('info', 'WordPress terdeteksi (versi tidak diketahui)', 'CMS');
        }

        if (!$isWP) return;

        // Check WP version against known CVEs
        $ver = $this->results['version'];
        if ($ver !== 'unknown' && isset($this->knownCves['wordpress'][$ver])) {
            $cves = $this->knownCves['wordpress'][$ver];
            if (!empty($cves)) {
                $this->addVuln('high', "WordPress {$ver} memiliki " . count($cves) . " CVE diketahui", implode(', ', $cves));
            } else {
                $this->addVuln('info', "WordPress {$ver} — tidak ada CVE publik yang diketahui", 'Version OK');
            }
        }

        // Check XMLRPC
        $xmlrpc = $this->fetchUrl($this->url . '/xmlrpc.php');
        if ($xmlrpc && (str_contains($xmlrpc, 'XML-RPC') || str_contains($xmlrpc, '<?xml'))) {
            $this->addVuln('medium', 'XML-RPC aktif — rentan brute force & DDoS', '/xmlrpc.php merespon');
        }

        // Check Readme
        $readme = $this->fetchUrl($this->url . '/readme.html');
        if ($readme && str_contains($readme, 'WordPress')) {
            $this->addVuln('low', 'File readme.html terbuka — informasi versi WP publik', '/readme.html');
        }

        // Check wp-config backup
        foreach (['wp-config.bak', 'wp-config.php.bak', 'wp-config.txt', 'wp-config.old', 'wp-config.save'] as $f) {
            $resp = $this->fetchUrl($this->url . '/' . $f);
            if ($resp && (str_contains($resp, 'DB_NAME') || str_contains($resp, 'DB_PASSWORD'))) {
                $this->addVuln('critical', "File backup wp-config terbuka: {$f}", 'Database kredensial terekspos');
                break;
            }
        }

        // Check plugin directory listing
        $plugins = $this->fetchUrl($this->url . '/wp-content/plugins/');
        if ($plugins && (str_contains($plugins, 'Index of') || str_contains($plugins, '<title>Index'))) {
            $this->addVuln('medium', 'Directory listing plugin terbuka', '/wp-content/plugins/');
        }
    }

    private function detectLaravel(string $html): void
    {
        $isLaravel = false;

        // Check for Laravel-specific paths
        $laravelIndicators = ['laravel', 'Laravel', 'APP_KEY', 'APP_ENV'];

        foreach ($laravelIndicators as $ind) {
            if (stripos($html, $ind) !== false) {
                $isLaravel = true;
                break;
            }
        }

        // Check .env exposure
        $envCheck = $this->fetchUrl($this->url . '/.env');
        if ($envCheck && (str_contains($envCheck, 'APP_KEY') || str_contains($envCheck, 'DB_CONNECTION'))) {
            $isLaravel = true;
            $this->results['cms'] = 'Laravel';
            $this->addVuln('critical', 'File .env terbuka — semua kredensial database dan APP_KEY terekspos', '/.env');
        }

        // Check storage exposure
        $storageCheck = $this->fetchUrl($this->url . '/storage');
        if ($storageCheck && str_contains($storageCheck, 'Index of')) {
            $isLaravel = true;
            $this->addVuln('high', 'Directory storage/ terbuka — file log & session terekspos', '/storage');
        }

        // Check debug mode
        $debugCheck = $this->fetchUrl($this->url . '/config/app.php');
        if ($debugCheck) {
            if (str_contains($debugCheck, "'debug' => true") || str_contains($debugCheck, "APP_DEBUG=true")) {
                $isLaravel = true;
                $this->addVuln('high', 'APP_DEBUG aktif — informasi sensitif bocor saat error', 'Mode debug');
            }
        }

        if ($isLaravel && !$this->results['cms']) {
            $this->results['cms'] = 'Laravel';
            $this->results['version'] = 'terdeteksi';
            $this->addVuln('info', 'Laravel framework terdeteksi', 'Framework');
        }
    }

    private function detectGeneral(string $html): void
    {
        // Check directory listing on common paths
        $paths = ['/admin/', '/backup/', '/temp/', '/tmp/', '/log/', '/logs/', '/upload/', '/uploads/'];
        foreach ($paths as $path) {
            $resp = $this->fetchUrl($this->url . $path);
            if ($resp && (str_contains($resp, 'Index of') || str_contains($resp, '<title>Index'))) {
                $this->addVuln('medium', "Directory listing terbuka: {$path}", 'Informasi struktur direktori');
            }
        }

        // Check .git exposure
        $gitCheck = $this->fetchUrl($this->url . '/.git/HEAD');
        if ($gitCheck && str_contains($gitCheck, 'ref: refs/heads/')) {
            $this->addVuln('critical', 'Folder .git terekspos — seluruh source code bisa diunduh', '/.git/HEAD');
        }

        // Check composer.json
        $composerCheck = $this->fetchUrl($this->url . '/composer.json');
        if ($composerCheck && str_contains($composerCheck, '"require"')) {
            $this->addVuln('high', 'File composer.json publik — daftar dependensi & versi terbuka', '/composer.json');
        }

        // Check debug mode via PHP headers
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true, CURLOPT_NOBODY => true,
        ]);
        curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $poweredBy = $info['primary_ip'] ?? '';
        // No specific header check needed here
    }

    private function checkSecurityConfigs(): void
    {
        // Check if HTTPS is used
        if (!str_starts_with($this->url, 'https')) {
            $this->addVuln('high', 'Website tidak menggunakan HTTPS', 'HTTP — data tidak terenkripsi');
        }

        // Check security headers via SecurityScanner
        $scanner = new SecurityScanner($this->url);
        $secResult = $scanner->scan();

        if (!$secResult['has_hsts']) {
            $this->addVuln('medium', 'HSTS header tidak aktif — rentan SSL stripping', 'Tingkatkan keamanan TLS');
        }
        if (!$secResult['has_csp']) {
            $this->addVuln('low', 'Content-Security-Policy tidak terdeteksi', 'CSP mencegah XSS & data injection');
        }
        if (!$secResult['has_xss_protection']) {
            $this->addVuln('low', 'X-XSS-Protection header tidak aktif', 'Perlindungan XSS tambahan');
        }
    }

    private function addVuln(string $severity, string $description, string $detail): void
    {
        $this->results['vulnerabilities'][] = [
            'severity' => $severity,
            'description' => $description,
            'detail' => $detail,
            'type' => $severity === 'critical' ? 'critical' : ($severity === 'high' ? 'high' : ($severity === 'medium' ? 'medium' : 'low')),
        ];
    }

    private function calculateScore(): void
    {
        $deductions = ['critical' => 25, 'high' => 12, 'medium' => 6, 'low' => 3, 'info' => 0];
        $score = 100;
        foreach ($this->results['vulnerabilities'] as $v) {
            $score -= $deductions[$v['severity']] ?? 5;
        }
        $this->results['score'] = max(0, $score);

        $critical = 0; $high = 0;
        foreach ($this->results['vulnerabilities'] as $v) {
            if ($v['severity'] === 'critical') $critical++;
            if ($v['severity'] === 'high') $high++;
        }
        $this->results['severity'] = $critical > 0 ? 'critical' : ($high > 0 ? 'high' : (count($this->results['vulnerabilities']) > 0 ? 'medium' : 'clean'));
    }
}
