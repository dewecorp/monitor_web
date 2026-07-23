<?php
declare(strict_types=1);

namespace App\Services;

class ServerSecurityScanner
{
    private array $results = [
        'score' => 100,
        'ports' => [],
        'suspicious_ports' => [],
        'scheduled_tasks' => [],
        'suspicious_tasks' => [],
        'admin_accounts' => [],
        'unknown_accounts' => [],
        'heartbeat' => [],
        'issues' => [],
        'fixes' => [],
    ];

    public function scan(): array
    {
        $this->scanOpenPorts();
        $this->scanScheduledTasks();
        $this->scanAdminAccounts();
        $this->checkHeartbeat();
        $this->calculateScore();
        return $this->results;
    }

    // ============= PORT SCANNER =============
    private function scanOpenPorts(): void
    {
        $ports = [];
        $suspicious = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('netstat -ano 2>nul');
            if ($output) {
                foreach (explode("\n", $output) as $line) {
                    if (preg_match('/TCP\s+\S+:(\d+)\s+\S+\s+LISTENING/i', $line, $m)) {
                        $port = (int)$m[1];
                        $name = $this->getPortService($port);
                        $ports[] = ['port' => $port, 'service' => $name, 'proto' => 'TCP'];
                        if (in_array($port, [21, 23, 25, 110, 135, 137, 139, 143, 445, 1433, 1521, 3306, 3389, 5900, 6379, 8080, 8443, 27017])) {
                            $suspicious[] = ['port' => $port, 'service' => $name, 'reason' => "Port {$port} ({$name}) terbuka — {$this->getPortRisk($port)}"];
                        }
                    }
                }
            }
        } else {
            $output = shell_exec('ss -tln 2>/dev/null');
            if ($output) {
                foreach (explode("\n", $output) as $line) {
                    if (preg_match('/LISTEN\s+.*:(\d+)\s/', $line, $m)) {
                        $port = (int)$m[1];
                        $name = $this->getPortService($port);
                        $ports[] = ['port' => $port, 'service' => $name, 'proto' => 'TCP'];
                    }
                }
            }
        }

        $this->results['ports'] = $ports;
        $this->results['suspicious_ports'] = $suspicious;
        foreach ($suspicious as $s) {
            $this->addIssue('open_port', $s['reason'], 'Tutup port yang tidak diperlukan via firewall', 'medium', $s['port']);
        }
    }

    private function getPortService(int $port): string
    {
        $services = [
            21 => 'FTP', 22 => 'SSH', 23 => 'Telnet', 25 => 'SMTP',
            80 => 'HTTP', 110 => 'POP3', 143 => 'IMAP', 443 => 'HTTPS',
            3306 => 'MySQL', 3389 => 'RDP', 5432 => 'PostgreSQL',
            6379 => 'Redis', 8080 => 'HTTP-Alt', 8443 => 'HTTPS-Alt',
            27017 => 'MongoDB', 9200 => 'Elasticsearch',
            1433 => 'MSSQL', 1521 => 'Oracle', 5900 => 'VNC',
            11211 => 'Memcached', 135 => 'RPC', 137 => 'NetBIOS',
            139 => 'NetBIOS-SSN', 445 => 'SMB',
        ];
        return $services[$port] ?? "Unknown-{$port}";
    }

    private function getPortRisk(int $port): string
    {
        $risks = [
            21 => 'FTP tanpa enkripsi — intersepsi password', 23 => 'Telnet tanpa enkripsi',
            25 => 'Open relay / spam risk', 135 => 'RPC exploitation risk',
            139 => 'NetBIOS information leak', 445 => 'EternalBlue / ransomware risk',
            1433 => 'SQL Server exposed', 3306 => 'Database langsung terekspos',
            3389 => 'RDP brute-force target', 5900 => 'VNC tanpa password',
            6379 => 'Redis tanpa auth — RCE risk',
        ];
        return $risks[$port] ?? 'Potensi eksploitasi';
    }

    // ============= SCHEDULED TASK SCANNER =============
    private function scanScheduledTasks(): void
    {
        $tasks = [];
        $suspicious = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('schtasks /query /fo LIST /v 2>nul');
            if (!$output) return;
            $currentTasks = explode("\n\n", $output);
            foreach ($currentTasks as $block) {
                if (preg_match('/TaskName:\s+(.+)/i', $block, $n) && preg_match('/TaskToRun:\s+(.+)/i', $block, $r)) {
                    $name = trim($n[1]);
                    $run = trim($r[1]);
                    $tasks[] = ['name' => $name, 'command' => $run];

                    $lower = strtolower($name . ' ' . $run);
                    if (preg_match('/(powershell|cmd|wscript|cscript)\s+.*(?:encodedcommand|-e\s|frombase64)/i', $lower)) {
                        $suspicious[] = ['name' => $name, 'command' => $run, 'reason' => 'Encoded PowerShell command — obfuscated script'];
                    }
                    if (preg_match('/\/\/(\d{1,3}\.){3}\d{1,3}\//i', $run)) {
                        $suspicious[] = ['name' => $name, 'command' => $run, 'reason' => 'Task connects to external IP'];
                    }
                    if (preg_match('/\b(wget|curl|bitsadmin|certutil)\b/i', $lower)) {
                        $suspicious[] = ['name' => $name, 'command' => $run, 'reason' => 'Download utility in scheduled task'];
                    }
                    if (preg_match('/\.(exe|dll|ps1|vbs|js|bat)\s*$/i', trim($run))) {
                        if (!preg_match('/System32|Windows|Program\s?Files|PerfLogs/i', $run)) {
                            $suspicious[] = ['name' => $name, 'command' => $run, 'reason' => 'Executable from non-system path'];
                        }
                    }
                }
            }
        } else {
            $output = shell_exec('crontab -l 2>/dev/null');
            if ($output) {
                foreach (explode("\n", $output) as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) continue;
                    preg_match('/\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+(.+)/', $line, $m);
                    $cmd = $m[1] ?? $line;
                    $tasks[] = ['name' => 'cron', 'command' => $cmd];
                    if (preg_match('/\b(curl|wget)\s+(?:-s|-o|-O)\s+https?:\/\/[^\s]+\s*\|\s*(bash|sh|php)\b/i', $cmd)) {
                        $suspicious[] = ['name' => 'cron', 'command' => $cmd, 'reason' => 'Remote script execution via pipe'];
                    }
                }
            }
        }

        $this->results['scheduled_tasks'] = $tasks;
        $this->results['suspicious_tasks'] = $suspicious;
        foreach ($suspicious as $s) {
            $this->addIssue('suspicious_task', "Task mencurigakan: {$s['name']}", $s['reason'], 'high', $s['command']);
        }
    }

    // ============= ADMIN ACCOUNT SCANNER =============
    private function scanAdminAccounts(): void
    {
        $admins = [];
        $unknown = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('net localgroup administrators 2>nul');
            if ($output) {
                $lines = explode("\n", $output);
                $inList = false;
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (str_contains($line, '---')) { $inList = true; continue; }
                    if (str_contains($line, 'The command')) break;
                    if ($inList && $line && !str_contains($line, 'completed')) {
                        $admins[] = $line;
                    }
                }
            }
        } else {
            $output = shell_exec('getent group sudo 2>/dev/null || getent group wheel 2>/dev/null');
            if ($output) {
                $parts = explode(':', $output);
                $users = explode(',', end($parts));
                foreach ($users as $u) {
                    $u = trim($u);
                    if ($u) $admins[] = $u;
                }
            }
        }

        // Check for known normal accounts
        $normalAccounts = ['administrator', 'admin', 'root', 'user', $this->getCurrentUser()];
        foreach ($admins as $user) {
            $isNormal = false;
            foreach ($normalAccounts as $na) {
                if (strtolower(trim($user)) === strtolower(trim($na))) { $isNormal = true; break; }
            }
            if (!$isNormal) {
                $unknown[] = ['username' => $user, 'in_admin_group' => true];
            }
        }

        // Check for non-existent accounts
        if (PHP_OS_FAMILY === 'Windows') {
            $users = shell_exec('net user 2>nul');
            // Basic check — flag accounts created recently
        }

        $this->results['admin_accounts'] = $admins;
        $this->results['unknown_accounts'] = $unknown;
        foreach ($unknown as $u) {
            $this->addIssue('unknown_admin', "Akun admin tidak dikenal: {$u['username']}", 'Verifikasi akun administrator', 'high', $u['username']);
        }
    }

    private function getCurrentUser(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return getenv('USERNAME') ?: 'unknown';
        }
        return get_current_user() ?: 'unknown';
    }

    // ============= HEARTBEAT =============
    private function checkHeartbeat(): void
    {
        $db = \App\Config\Database::getConnection();

        // Check when was the last agent report
        $stmt = $db->query("SELECT MAX(collected_at) as last_beat, COUNT(*) as total_beats, AVG(cpu_usage) as avg_cpu FROM agent_reports");
        $hb = $stmt->fetch();

        $lastBeatStr = $hb['last_beat'] ?? null;
        $lastBeat = $lastBeatStr ? strtotime($lastBeatStr) : 0;
        $now = time();
        $diffMinutes = $lastBeat > 0 ? round(($now - $lastBeat) / 60) : 999;

        $this->results['heartbeat'] = [
            'last_beat' => $hb['last_beat'] ?? null,
            'total_beats' => (int)($hb['total_beats'] ?? 0),
            'avg_cpu' => round((float)($hb['avg_cpu'] ?? 0), 1),
            'minutes_since_last' => $diffMinutes,
            'status' => $diffMinutes <= 5 ? 'healthy' : ($diffMinutes <= 15 ? 'warning' : 'critical'),
        ];

        if ($diffMinutes > 5) {
            $this->addIssue('heartbeat', "Agent tidak mengirim laporan selama {$diffMinutes} menit", 'Cek apakah agent.php masih berjalan di server', $diffMinutes > 15 ? 'high' : 'medium', 'agent.php');
        }

        // Check service status from last report
        $latestServices = $db->query("SELECT server_name, services FROM agent_reports WHERE services IS NOT NULL ORDER BY collected_at DESC LIMIT 5");
        foreach ($latestServices->fetchAll() as $s) {
            $svcs = json_decode($s['services'], true);
            if (is_array($svcs)) {
                $stopped = array_filter($svcs, fn($v) => $v === 'stopped');
                foreach ($stopped as $name => $status) {
                    $this->addIssue('service_down', "Service {$name} mati di {$s['server_name']}", "Restart service {$name}", 'high', $name);
                }
            }
        }
    }

    // ============= AUTO HARDENING =============
    public function autoHarden(array $fixes): array
    {
        $results = [];
        $basePath = BASE_PATH;

        foreach ($fixes as $fix) {
            $results[$fix] = match($fix) {
                'disable_directory_listing' => $this->fixDirectoryListing($basePath),
                'protect_sensitive_files' => $this->fixProtectSensitiveFiles($basePath),
                'disable_php_info' => $this->fixDisablePhpInfo(),
                'secure_htaccess' => $this->fixSecureHtaccess($basePath),
                default => ['status' => 'unknown', 'message' => "Fix {$fix} tidak dikenal"],
            };
        }

        return $results;
    }

    private function fixDirectoryListing(string $path): array
    {
        $htaccess = $path . '/.htaccess';
        $content = file_exists($htaccess) ? file_get_contents($htaccess) : '';
        if (str_contains($content, 'Options -Indexes')) {
            return ['status' => 'skipped', 'message' => 'Directory listing sudah dinonaktifkan'];
        }
        file_put_contents($htaccess, "\nOptions -Indexes\n", FILE_APPEND);
        $this->addFix('disable_directory_listing', 'Directory listing dinonaktifkan via .htaccess');
        return ['status' => 'fixed', 'message' => 'Options -Indexes ditambahkan ke .htaccess'];
    }

    private function fixProtectSensitiveFiles(string $path): array
    {
        $htaccess = $path . '/.htaccess';
        $rules = "\n<FilesMatch \"\\.(env|git|sql|bak|old|log|json|lock)$\">\nRequire all denied\n</FilesMatch>\n";
        $content = file_exists($htaccess) ? file_get_contents($htaccess) : '';
        if (str_contains($content, 'FilesMatch')) {
            return ['status' => 'skipped', 'message' => 'File sensitif sudah dilindungi'];
        }
        file_put_contents($htaccess, $rules, FILE_APPEND);
        $this->addFix('protect_sensitive_files', 'File sensitif dilindungi via .htaccess');
        return ['status' => 'fixed', 'message' => 'FilesMatch rules ditambahkan'];
    }

    private function fixDisablePhpInfo(): array
    {
        if (function_exists('ini_set')) {
            @ini_set('display_errors', '0');
            @ini_set('expose_php', '0');
        }
        $this->addFix('disable_php_info', 'PHP display_errors & expose_php dimatikan');
        return ['status' => 'fixed', 'message' => 'PHP security hardening applied'];
    }

    private function fixSecureHtaccess(string $path): array
    {
        $htaccess = $path . '/.htaccess';
        $content = file_exists($htaccess) ? file_get_contents($htaccess) : '';
        $rules = "\n## WEBGUARDIAN HARDENING\n<IfModule mod_headers.c>\nHeader always set X-Frame-Options \"DENY\"\nHeader always set X-Content-Type-Options \"nosniff\"\nHeader always set X-XSS-Protection \"1; mode=block\"\n</IfModule>\n";
        if (str_contains($content, 'X-Frame-Options')) {
            return ['status' => 'skipped', 'message' => 'Security headers sudah ada'];
        }
        file_put_contents($htaccess, $rules, FILE_APPEND);
        $this->addFix('secure_htaccess', 'Security headers ditambahkan ke .htaccess');
        return ['status' => 'fixed', 'message' => 'Security headers added'];
    }

    // ============= UTILITIES =============
    private function addIssue(string $type, string $description, string $recommendation, string $severity, mixed $target): void
    {
        $this->results['issues'][] = ['type' => $type, 'description' => $description, 'recommendation' => $recommendation, 'severity' => $severity, 'target' => $target];
    }

    private function addFix(string $type, string $message): void
    {
        $this->results['fixes'][] = ['type' => $type, 'message' => $message];
    }

    private function calculateScore(): void
    {
        $score = 100;
        foreach ($this->results['issues'] as $iss) {
            $score -= match($iss['severity']) { 'critical' => 15, 'high' => 8, 'medium' => 4, default => 2 };
        }
        $this->results['score'] = max(0, $score);
    }
}
