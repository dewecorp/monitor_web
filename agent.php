<?php
/**
 * WEBGUARDIAN Monitoring Agent
 *
 * Deploy on servers you want to monitor.
 * Collects: CPU, RAM, Disk, Processes, Services, PHP info, File Integrity
 *
 * Usage:
 *   1. Generate API Key di WEBGUARDIAN Dashboard > Settings > API Keys
 *   2. Upload agent.php ke server tujuan
 *   3. Setup cron:
 *      * /5 * * * * php /path/to/agent.php https://your-webguardian.com/api/agent/report YOUR_API_KEY SERVER_NAME
 *
 * Or via web with token:
 *   https://server.com/agent.php?server=SERVER_NAME&key=API_KEY&hub=WEBGUARDIAN_URL
 */

declare(strict_types=1);

// ========== CONFIG ==========
$hubUrl = $argv[1] ?? $_GET['hub'] ?? 'http://localhost/monitor_web';
$apiKey = $argv[2] ?? $_GET['key'] ?? '';
$serverName = $argv[3] ?? $_GET['server'] ?? gethostname();
// ============================

if (!$apiKey || !$hubUrl) {
    die("Usage: php agent.php <HUB_URL> <API_KEY> [SERVER_NAME]\nOr: ?hub=URL&key=KEY&server=NAME\n");
}

function getCpuUsage(): float
{
    if (PHP_OS_FAMILY === 'Windows') {
        $output = shell_exec('wmic cpu get loadpercentage');
        if ($output && preg_match('/\d+/', $output, $m)) return (float)$m[0];
        return 0;
    }
    $load = sys_getloadavg();
    return round($load[0] * 10, 1);
}

function getMemoryUsage(): array
{
    if (PHP_OS_FAMILY === 'Windows') {
        $output = shell_exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory');
        if ($output) {
            $lines = explode("\n", trim($output));
            if (count($lines) >= 2) {
                $parts = preg_split('/\s+/', trim($lines[1]));
                if (count($parts) >= 2) {
                    $total = (int)$parts[0];
                    $free = (int)$parts[1];
                    return ['total_mb' => round($total / 1024), 'used_mb' => round(($total - $free) / 1024), 'usage_pct' => $total > 0 ? round((1 - $free / $total) * 100, 1) : 0];
                }
            }
        }
        return ['total_mb' => 0, 'used_mb' => 0, 'usage_pct' => 0];
    }
    $mem = file_get_contents('/proc/meminfo');
    preg_match('/MemTotal:\s+(\d+)/', $mem, $total);
    preg_match('/MemAvailable:\s+(\d+)/', $mem, $avail);
    $totalKb = (int)($total[1] ?? 0);
    $availKb = (int)($avail[1] ?? 0);
    return [
        'total_mb' => round($totalKb / 1024),
        'used_mb' => round(($totalKb - $availKb) / 1024),
        'usage_pct' => $totalKb > 0 ? round((1 - $availKb / $totalKb) * 100, 1) : 0,
    ];
}

function getDiskUsage(): array
{
    $total = @disk_total_space('/') ?: 0;
    $free = @disk_free_space('/') ?: 0;
    return [
        'total_gb' => round($total / 1073741824, 1),
        'used_gb' => round(($total - $free) / 1073741824, 1),
        'usage_pct' => $total > 0 ? round((1 - $free / $total) * 100, 1) : 0,
    ];
}

function getPhpInfo(): array
{
    return [
        'version' => PHP_VERSION,
        'sapi' => PHP_SAPI,
        'os' => PHP_OS_FAMILY . ' ' . php_uname('r'),
        'extensions' => implode(', ', get_loaded_extensions()),
        'disabled_functions' => ini_get('disable_functions') ?: 'none',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'display_errors' => ini_get('display_errors'),
        'allow_url_fopen' => ini_get('allow_url_fopen'),
    ];
}

function getServices(): array
{
    $services = [];
    $checks = [
        'apache' => ['win' => 'Apache2.4', 'nix' => 'apache2'],
        'nginx' => ['win' => '', 'nix' => 'nginx'],
        'mysql' => ['win' => 'MySQL', 'nix' => 'mysql'],
        'php_fpm' => ['win' => '', 'nix' => 'php-fpm'],
        'redis' => ['win' => 'Redis', 'nix' => 'redis-server'],
        'docker' => ['win' => 'docker', 'nix' => 'docker'],
    ];

    foreach ($checks as $name => $cfg) {
        if (PHP_OS_FAMILY === 'Windows' && $cfg['win']) {
            $output = shell_exec("sc query \"{$cfg['win']}\" 2>nul");
            $services[$name] = $output && str_contains($output, 'RUNNING') ? 'running' : 'stopped';
        } elseif (PHP_OS_FAMILY !== 'Windows' && $cfg['nix']) {
            $output = shell_exec("systemctl is-active {$cfg['nix']} 2>/dev/null");
            $services[$name] = $output ? trim($output) : 'unknown';
        } else {
            $services[$name] = 'unknown';
        }
    }
    return $services;
}

function getLoadAverage(): string
{
    if (PHP_OS_FAMILY === 'Windows') return 'N/A';
    $load = sys_getloadavg();
    return implode(' ', array_map(fn($v) => round($v, 2), $load));
}

function getUptime(): string
{
    if (PHP_OS_FAMILY === 'Windows') {
        $output = shell_exec('wmic os get lastbootuptimemanagement');
        return trim($output ?? 'N/A');
    }
    $uptime = file_get_contents('/proc/uptime');
    $seconds = (int)explode(' ', $uptime)[0];
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    return "{$days}d {$hours}h";
}

function getRunningProcesses(): int
{
    if (PHP_OS_FAMILY === 'Windows') {
        $output = shell_exec('tasklist /NH 2>nul');
        return $output ? substr_count($output, "\n") : 0;
    }
    return (int)shell_exec('ps aux --no-headers 2>/dev/null | wc -l') ?: 0;
}

// Collect data
$cpu = getCpuUsage();
$mem = getMemoryUsage();
$disk = getDiskUsage();

$payload = [
    'server_name' => $serverName,
    'hostname' => gethostname(),
    'ip' => $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()),
    'collected_at' => date('Y-m-d H:i:s'),
    'uptime' => getUptime(),
    'cpu' => [
        'usage_pct' => $cpu,
    ],
    'memory' => $mem,
    'disk' => $disk,
    'load_average' => getLoadAverage(),
    'processes' => getProcessList(),
    'open_ports' => getOpenPorts(),
    'services' => getServiceStatus(),
    'uptime' => getUptime(),
    'php' => getPhpInfo(),
    'php_version' => PHP_VERSION,
];

// Send to hub
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => rtrim($hubUrl, '/') . '/api/agent/report',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (php_sapi_name() === 'cli') {
    echo "WEBGUARDIAN Agent Report\n";
    echo "Server: {$serverName}\n";
    echo "CPU: {$cpu}% | RAM: {$mem['usage_pct']}% | Disk: {$disk['usage_pct']}%\n";
    echo "PHP: {$payload['php_version']} | Sent: " . date('H:i:s') . "\n";
    echo "Hub response (" . $httpCode . "): " . $response . "\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['sent' => true, 'http' => $httpCode, 'time' => date('Y-m-d H:i:s')]);
}

function getProcessList(): array
{
    $processes = [];
    if (PHP_OS_FAMILY === 'Windows') {
        $output = shell_exec('tasklist /FO CSV /NH 2>nul');
        if ($output) {
            $lines = array_filter(explode("\n", trim($output)));
            foreach ($lines as $line) {
                $parts = str_getcsv($line);
                if (count($parts) >= 5) {
                    $processes[] = [
                        'name' => $parts[0],
                        'pid' => (int)$parts[1],
                        'mem_kb' => (int)str_replace(',', '', $parts[4]),
                        'status' => 'running',
                    ];
                }
            }
        }
    } else {
        $output = shell_exec('ps aux --sort=-%mem --no-headers 2>/dev/null | head -20');
        if ($output) {
            foreach (explode("\n", trim($output)) as $line) {
                $parts = preg_split('/\s+/', trim($line), 11);
                if (count($parts) >= 11) {
                    $processes[] = [
                        'user' => $parts[0],
                        'pid' => (int)$parts[1],
                        'cpu' => (float)$parts[2],
                        'mem' => (float)$parts[3],
                        'cmd' => $parts[10],
                    ];
                }
            }
        }
    }
    return array_slice($processes, 0, 15);
}

function getOpenPorts(): array
{
    $ports = [];
    if (PHP_OS_FAMILY === 'Windows') {
        $output = shell_exec('netstat -an 2>nul');
        if ($output) {
            foreach (explode("\n", $output) as $line) {
                if (preg_match('/TCP\s+(\S+):(\d+)\s+(\S+)\s+LISTENING/i', $line, $m)) {
                    $ports[] = ['port' => (int)$m[2], 'ip' => $m[1], 'proto' => 'TCP'];
                }
            }
        }
    } else {
        $output = shell_exec('ss -tln 2>/dev/null');
        if ($output) {
            foreach (explode("\n", $output) as $line) {
                if (preg_match('/LISTEN\s+.*:(\d+)\s/', $line, $m)) {
                    $ports[] = ['port' => (int)$m[1], 'ip' => '0.0.0.0', 'proto' => 'TCP'];
                }
            }
        }
    }
    return $ports;
}

function getServiceStatus(): array
{
    $services = [];
    $checks = [
        'apache' => PHP_OS_FAMILY === 'Windows' ? 'Apache2.4' : 'apache2',
        'nginx' => PHP_OS_FAMILY === 'Windows' ? '' : 'nginx',
        'mysql' => PHP_OS_FAMILY === 'Windows' ? 'MySQL' : 'mysql',
        'php_fpm' => PHP_OS_FAMILY === 'Windows' ? '' : 'php-fpm',
        'redis' => PHP_OS_FAMILY === 'Windows' ? 'Redis' : 'redis-server',
    ];

    foreach ($checks as $name => $svc) {
        if (!$svc) { $services[$name] = 'unknown'; continue; }
        
        if (PHP_OS_FAMILY === 'Windows') {
            $out = shell_exec("sc query \"{$svc}\" 2>nul");
            $services[$name] = $out && str_contains($out, 'RUNNING') ? 'running' : 'stopped';
        } else {
            $out = shell_exec("systemctl is-active {$svc} 2>/dev/null");
            $services[$name] = $out ? trim($out) : 'unknown';
        }
    }
    return $services;
}

function getSystemUptime(): string
{
    if (PHP_OS_FAMILY === 'Windows') {
        $out = shell_exec('wmic os get lastbootuptime 2>nul');
        return $out ? trim($out) : 'N/A';
    }
    $uptime = file_get_contents('/proc/uptime');
    $seconds = (int)explode(' ', $uptime)[0];
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    return "{$days}d {$hours}h";
}
