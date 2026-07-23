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
            $stmt = $db->prepare("INSERT INTO agent_reports (server_name, hostname, ip_address, cpu_usage, memory_usage, memory_total_mb, memory_used_mb, disk_usage, disk_total_gb, disk_used_gb, load_average, processes, uptime, php_version, os_name, os_version, web_server, database_version, collected_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
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
                is_array($input['processes']) ? count($input['processes']) : (int)($input['processes'] ?? 0),
                $input['uptime'] ?? '',
                $input['php_version'] ?? PHP_VERSION,
                $input['os']['name'] ?? $input['os_name'] ?? PHP_OS_FAMILY,
                $input['os']['version'] ?? $input['os_version'] ?? '',
                $_SERVER['SERVER_SOFTWARE'] ?? $input['web_server'] ?? 'Unknown',
                $input['database_version'] ?? '',
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
            SELECT a.server_name, MAX(a.collected_at) as last_report, COUNT(*) as total_reports,
                   AVG(a.cpu_usage) as cpu,
                   AVG(a.memory_usage) as memory,
                   AVG(a.disk_usage) as disk
            FROM agent_reports a GROUP BY a.server_name ORDER BY last_report DESC
        ")->fetchAll();

        // Get latest data for each server
        foreach ($servers as &$srv) {
            $stmt = $db->prepare("SELECT php_version, uptime, os_name, os_version, web_server, database_version as db_version, hostname, ip_address as ip, load_average as load_avg FROM agent_reports WHERE server_name = ? ORDER BY collected_at DESC LIMIT 1");
            $stmt->execute([$srv['server_name']]);
            $latest = $stmt->fetch();
            if ($latest) {
                $srv['php_version'] = $latest['php_version'];
                $srv['uptime'] = $latest['uptime'];
                $srv['os_name'] = $latest['os_name'];
                $srv['os_version'] = $latest['os_version'];
                $srv['web_server'] = $latest['web_server'];
                $srv['db_version'] = $latest['db_version'];
                $srv['hostname'] = $latest['hostname'];
                $srv['ip'] = $latest['ip'];
                $srv['load_avg'] = $latest['load_avg'];
            }
        }
        unset($srv);
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
