<?php
declare(strict_types=1);

namespace App\Services;

class HardeningCheck
{
    private string $basePath;
    private string $url;
    private array $results = [
        'score' => 0,
        'issues' => [],
        'passed' => [],
        'fixable' => [],
    ];

    private array $dangerousFunctions = [
        'eval', 'assert', 'system', 'exec', 'shell_exec', 'passthru',
        'proc_open', 'popen', 'pcntl_exec', 'create_function',
        'phpinfo', 'show_source', 'symlink', 'link', 'dl',
    ];

    private array $criticalFiles = [
        '.env', 'config.php', 'wp-config.php', '.htaccess',
        'composer.json', 'composer.lock', 'artisan',
    ];

    public function __construct(string $basePath, string $url = '')
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->url = $url ? rtrim($url, '/') : '';
    }

    public function check(): array
    {
        $score = 0;

        // Local filesystem checks (only if basePath provided)
        if ($this->basePath) {
            $score += $this->checkFilePermissions();
            $score += $this->checkDirectoryListing();
        }

        // Server-wide checks
        $score += $this->checkDangerousFunctions();
        $score += $this->checkPhpConfig();

        // Remote checks (only if URL provided)
        if ($this->url) {
            $score += $this->checkSecurityHeaders();
            $score += $this->checkTlsConfig();
        }

        $this->results['score'] = min(100, max(0, $score));
        return $this->results;
    }

    private function checkFilePermissions(): int
    {
        $points = 0;
        $checked = 0;

        foreach ($this->criticalFiles as $file) {
            $fullPath = $this->basePath . '/' . $file;
            if (!file_exists($fullPath)) continue;
            $checked++;

            $perms = fileperms($fullPath);
            $permStr = substr(sprintf('%o', $perms), -4);

            if ($perms & 0x0004) { // World-readable
                $this->addIssue('file_permission', 'File ' . $file . ' world-readable (' . $permStr . ')', 'Set permission ke 640 atau 600', 'medium', $fullPath);
            } else {
                $this->addPassed('File permission: ' . $file . ' (' . $permStr . ')');
                $points += 5;
            }

            if ($perms & 0x0002) { // World-writable
                $this->addIssue('file_permission', 'File ' . $file . ' world-writable! (' . $permStr . ')', 'Set permission ke 640', 'critical', $fullPath);
            }

            // Check owner
            $user = getmyuid();
            $fileOwner = fileowner($fullPath);
            if ($fileOwner !== false && $fileOwner !== $user) {
                $this->addIssue('file_owner', 'File ' . $file . ' bukan milik user web (' . $fileOwner . ')', 'chown file ke user web', 'medium', $fullPath);
            }
        }

        // Check upload directories
        foreach (['uploads', 'upload', 'storage', 'tmp', 'temp', 'cache'] as $dir) {
            $fullPath = $this->basePath . '/' . $dir;
            if (!is_dir($fullPath)) continue;

            $perms = fileperms($fullPath);
            if ($perms & 0x0002) {
                $this->addIssue('dir_writable', 'Direktori ' . $dir . ' world-writable', 'Batasi permission direktori', 'high', $fullPath);
            } else {
                $this->addPassed('Direktori ' . $dir . ' — permission aman');
                $points += 3;
            }
        }

        // Check .htaccess exists
        if (!file_exists($this->basePath . '/.htaccess')) {
            $this->addIssue('missing_htaccess', 'File .htaccess tidak ditemukan', 'Buat .htaccess untuk security rules', 'medium', $this->basePath . '/.htaccess');
        } else {
            $this->addPassed('.htaccess exists');
            $points += 5;
        }

        return $points;
    }

    private function checkDangerousFunctions(): int
    {
        $points = 10;
        $disabledStr = ini_get('disable_functions');
        $disabled = array_map('trim', explode(',', $disabledStr));

        foreach ($this->dangerousFunctions as $func) {
            if (!in_array($func, $disabled)) {
                $this->addIssue('dangerous_func', 'Fungsi berbahaya aktif: ' . $func . '()', 'Tambahkan ke disable_functions di php.ini', 'high', $func);
                $points -= 3;
            }
        }

        if ($points === 10) {
            $this->addPassed('Semua fungsi berbahaya telah dinonaktifkan');
        }

        return max(0, $points);
    }

    private function checkDirectoryListing(): int
    {
        $points = 5;
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? $this->basePath;

        // Try to detect directory listing via PHP ini
        $indexIgnore = ini_get('error_reporting'); // Not the right check

        // Check .htaccess for IndexIgnore / Options -Indexes
        $htaccessPath = $this->basePath . '/.htaccess';
        if (file_exists($htaccessPath)) {
            $htcontent = file_get_contents($htaccessPath);
            if (str_contains($htcontent, 'IndexIgnore') || str_contains($htcontent, '-Indexes')) {
                $this->addPassed('Directory listing telah dinonaktifkan');
                $points += 5;
            } else {
                $this->addIssue('dir_listing', 'Directory listing mungkin aktif', 'Tambahkan Options -Indexes ke .htaccess', 'medium', $htaccessPath);
                $points -= 2;
            }
        }

        return max(0, $points);
    }

    private function checkPhpConfig(): int
    {
        $points = 15;

        // display_errors
        if (ini_get('display_errors')) {
            $this->addIssue('php_display_errors', 'display_errors ON — informasi sensitif bocor', 'Set display_errors = Off di php.ini', 'high', 'PHP');
            $points -= 5;
        } else {
            $this->addPassed('display_errors Off');
        }

        // allow_url_fopen
        if (ini_get('allow_url_fopen')) {
            $this->addIssue('php_allow_url_fopen', 'allow_url_fopen ON — rentan RFI', 'Set allow_url_fopen = Off jika tidak diperlukan', 'medium', 'PHP');
            $points -= 3;
        } else {
            $this->addPassed('allow_url_fopen Off');
        }

        // expose_php
        if (ini_get('expose_php')) {
            $this->addIssue('php_expose', 'expose_php ON — versi PHP terekspos', 'Set expose_php = Off di php.ini', 'low', 'PHP');
            $points -= 2;
        } else {
            $this->addPassed('expose_php Off');
        }

        // file_uploads
        if (ini_get('file_uploads') && !ini_get('upload_tmp_dir')) {
            $this->addIssue('php_file_uploads', 'File upload aktif tanpa tmp dir spesifik', 'Set upload_tmp_dir ke path aman', 'low', 'PHP');
            $points -= 2;
        }

        // open_basedir
        if (ini_get('open_basedir')) {
            $this->addPassed('open_basedir aktif — membatasi akses file');
            $points += 5;
        } else {
            $this->addIssue('php_open_basedir', 'open_basedir tidak aktif', 'Set open_basedir untuk membatasi akses file sistem', 'medium', 'PHP');
            $points -= 3;
        }

        return max(0, $points);
    }

    private function checkSecurityHeaders(): int
    {
        $points = 10;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 Chrome/120',
        ]);
        curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $rawHeaders = curl_exec($ch);
        curl_close($ch);

        $headers = [];
        foreach (explode("\r\n", $rawHeaders) as $line) {
            if (strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                $headers[strtolower(trim($k))] = trim($v);
            }
        }

        $checks = [
            'strict-transport-security' => ['HSTS', 'high', 3],
            'x-frame-options' => ['X-Frame-Options', 'medium', 2],
            'x-content-type-options' => ['X-Content-Type-Options', 'medium', 2],
            'content-security-policy' => ['CSP', 'medium', 2],
            'referrer-policy' => ['Referrer-Policy', 'low', 1],
            'permissions-policy' => ['Permissions-Policy', 'low', 1],
        ];

        foreach ($checks as $header => [$label, $severity, $pts]) {
            if (isset($headers[$header])) {
                $this->addPassed("Header {$label} aktif");
                $points += $pts;
            } else {
                $this->addIssue('security_header', "Header {$label} tidak aktif", "Tambahkan header {$label}", $severity, $this->url);
                $points -= $pts;
            }
        }

        // Check Server header info leak
        if (isset($headers['server']) && !str_contains($headers['server'], 'Cloudflare')) {
            $this->addIssue('server_info', 'Server header menampilkan informasi: ' . $headers['server'], 'Set ServerTokens Prod di Apache', 'low', 'Apache');
            $points -= 1;
        }

        // Check X-Powered-By
        if (isset($headers['x-powered-by'])) {
            $this->addIssue('php_expose_header', 'X-Powered-By header: ' . $headers['x-powered-by'], 'Set expose_php = Off', 'low', 'PHP');
            $points -= 1;
        }

        return max(0, $points);
    }

    private function checkTlsConfig(): int
    {
        $points = 5;

        if (!str_starts_with($this->url, 'https')) {
            $this->addIssue('no_https', 'Website tidak menggunakan HTTPS', 'Aktifkan SSL/TLS certificate', 'critical', $this->url);
            return 0;
        }

        // Check TLS version via connection
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_NOBODY => true,
        ]);
        curl_exec($ch);
        $protocol = curl_getinfo($ch, CURLINFO_PROTOCOL) ?? 0;
        $sslVer = curl_getinfo($ch, CURLINFO_SSL_VERIFYRESULT) ?? -1;
        curl_close($ch);

        $this->addPassed('HTTPS aktif');
        $points += 5;

        // Check HSTS preload readiness
        $ch2 = curl_init();
        curl_setopt_array($ch2, [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
        ]);
        curl_exec($ch2);
        curl_close($ch2);

        return $points;
    }

    private function addIssue(string $type, string $description, string $recommendation, string $severity, string $target): void
    {
        $this->results['issues'][] = [
            'type' => $type,
            'description' => $description,
            'recommendation' => $recommendation,
            'severity' => $severity,
            'target' => $target,
        ];
    }

    private function addPassed(string $message): void
    {
        $this->results['passed'][] = $message;
    }
}
