<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ApiKey;

class AgentController
{
    public function report(): void
    {
        try {
            $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
            $key = '';
            if (preg_match('/Bearer\s+(.+)/i', $header, $m)) $key = $m[1];
            if (!$key) $key = $_GET['api_key'] ?? '';
            if (!$key) throw new \Exception('API key required');

            $apiKey = ApiKey::validate($key);
            if (!$apiKey) throw new \Exception('Invalid API key');

            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            if (!$input) throw new \Exception('Invalid JSON');

            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("INSERT INTO agent_reports (server_name, hostname, ip_address, cpu_usage, memory_usage, memory_total_mb, memory_used_mb, disk_usage, disk_total_gb, disk_used_gb, load_average, processes, uptime, php_version, collected_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $input['server_name'] ?? 'unknown',
                $input['hostname'] ?? '',
                $input['ip'] ?? '',
                (float)($input['cpu']['usage_pct'] ?? 0),
                (float)($input['memory']['usage_pct'] ?? 0),
                (int)($input['memory']['total_mb'] ?? 0),
                (int)($input['memory']['used_mb'] ?? 0),
                (float)($input['disk']['usage_pct'] ?? 0),
                (float)($input['disk']['total_gb'] ?? 0),
                (float)($input['disk']['used_gb'] ?? 0),
                $input['load_average'] ?? '',
                (int)($input['processes'] ?? 0),
                $input['uptime'] ?? '',
                $input['php_version'] ?? PHP_VERSION,
            ]);

            echo json_encode(['success' => true, 'message' => 'Report received']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'line' => $e->getLine()]);
        }
    }

    public function servers(): string
    {
        \App\Middleware\Auth::check();
        $db = \App\Config\Database::getConnection();
        $servers = $db->query("
            SELECT server_name, MAX(collected_at) as last_report, COUNT(*) as total_reports,
                   (SELECT cpu_usage FROM agent_reports a2 WHERE a2.server_name = a.server_name ORDER BY collected_at DESC LIMIT 1) as cpu,
                   (SELECT memory_usage FROM agent_reports a2 WHERE a2.server_name = a.server_name ORDER BY collected_at DESC LIMIT 1) as memory,
                   (SELECT disk_usage FROM agent_reports a2 WHERE a2.server_name = a.server_name ORDER BY collected_at DESC LIMIT 1) as disk,
                   (SELECT php_version FROM agent_reports a2 WHERE a2.server_name = a.server_name ORDER BY collected_at DESC LIMIT 1) as php_version,
                   (SELECT uptime FROM agent_reports a2 WHERE a2.server_name = a.server_name ORDER BY collected_at DESC LIMIT 1) as uptime
            FROM agent_reports a GROUP BY server_name ORDER BY last_report DESC
        ")->fetchAll();
        return view('agent.servers', ['servers' => $servers, 'pageTitle' => 'Server Monitoring']);
    }

    public function restartService(): void
    {
        \App\Middleware\Auth::check();
        $service = $_POST['service'] ?? '';
        $commands = [
            'apache' => PHP_OS_FAMILY === 'Windows' ? 'httpd -k restart' : 'systemctl restart apache2',
            'nginx' => PHP_OS_FAMILY === 'Windows' ? '' : 'systemctl restart nginx',
            'mysql' => PHP_OS_FAMILY === 'Windows' ? 'net stop MySQL && net start MySQL' : 'systemctl restart mysql',
            'php_fpm' => PHP_OS_FAMILY === 'Windows' ? '' : 'systemctl restart php8.3-fpm',
            'redis' => PHP_OS_FAMILY === 'Windows' ? '' : 'systemctl restart redis-server',
        ];
        if (!isset($commands[$service]) || !$commands[$service]) {
            $_SESSION['error'] = "Service {$service} tidak dikenal"; redirect('/agent/servers');
        }
        \App\Models\User::logActivity($_SESSION['user_id'], 'Restart Service', "Restart: {$service}");
        exec($commands[$service] . ' 2>&1', $output, $retCode);
        if ($retCode === 0) { $_SESSION['success'] = "{$service} berhasil direstart"; }
        else { $_SESSION['error'] = "Gagal restart {$service}: " . implode("\n", $output); }
        redirect('/agent/servers');
    }
}
