<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ApiKey;
use App\Models\Website;
use App\Models\MonitorLog;
use App\Models\SecurityLog;
use App\Models\SslLog;
use App\Models\TrafficLog;

class ApiController
{
    private ?array $apiKey = null;

    public function __construct()
    {
        $this->authenticate();
    }

    private function authenticate(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            $key = $m[1];
        } else {
            $key = $_GET['api_key'] ?? '';
        }

        if (!$key) {
            $this->error('API key required', 401);
        }

        $this->apiKey = ApiKey::validate($key);
        if (!$this->apiKey) {
            $this->error('Invalid or expired API key', 401);
        }
    }

    private function canWrite(): bool
    {
        return str_contains($this->apiKey['permissions'] ?? '', 'write');
    }

    private function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private function error(string $msg, int $code = 400): void
    {
        $this->json(['success' => false, 'error' => $msg], $code);
    }

    private function paginate(\PDOStatement $stmt, int $page, int $perPage): array
    {
        $total = $stmt->fetch()['total'];
        $totalPages = max(1, (int)ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        return ['total' => (int)$total, 'total_pages' => $totalPages, 'page' => $page, 'per_page' => $perPage, 'offset' => $offset];
    }

    // --- Websites ---
    public function websites(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $search = $_GET['search'] ?? '';

        $db = \App\Config\Database::getConnection();
        $where = $search ? "WHERE w.nama_website LIKE ? OR w.url LIKE ?" : "";
        $params = $search ? ["%{$search}%", "%{$search}%"] : [];

        $countStmt = $db->prepare("SELECT COUNT(*) as total FROM websites w {$where}");
        $countStmt->execute($params);
        $pagination = $this->paginate($countStmt, $page, $perPage);

        $stmt = $db->prepare("
            SELECT w.*, m.is_up, m.response_time_ms, m.status_code, m.checked_at as last_check,
                   s.score as security_score
            FROM websites w
            LEFT JOIN monitor_logs m ON m.website_id = w.id AND m.checked_at = (SELECT MAX(m2.checked_at) FROM monitor_logs m2 WHERE m2.website_id = w.id)
            LEFT JOIN security_logs s ON s.website_id = w.id AND s.checked_at = (SELECT MAX(s2.checked_at) FROM security_logs s2 WHERE s2.website_id = w.id)
            {$where}
            ORDER BY w.nama_website ASC LIMIT ? OFFSET ?
        ");
        $bind = $params;
        $bind[] = $perPage;
        $bind[] = $pagination['offset'];
        $stmt->execute($bind);

        $this->json([
            'success' => true,
            'data' => $stmt->fetchAll(),
            'pagination' => $pagination,
        ]);
    }

    public function websiteDetail(array $params): void
    {
        $website = Website::withLatestStatus();
        $filtered = array_values(array_filter($website, fn($w) => $w['id'] == $params['id']));
        if (empty($filtered)) $this->error('Website not found', 404);
        $this->json(['success' => true, 'data' => $filtered[0]]);
    }

    // --- Monitor Logs ---
    public function monitorLogs(array $params): void
    {
        $id = (int)$params['id'];
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 30)));
        $logs = MonitorLog::history($id, $limit);
        $this->json(['success' => true, 'data' => $logs]);
    }

    // --- Security Logs ---
    public function securityLogs(array $params): void
    {
        $id = (int)$params['id'];
        $log = SecurityLog::latestForWebsite($id);
        $this->json(['success' => true, 'data' => $log]);
    }

    // --- SSL Logs ---
    public function sslLogs(array $params): void
    {
        $id = (int)$params['id'];
        $log = SslLog::latestForWebsite($id);
        $this->json(['success' => true, 'data' => $log]);
    }

    // --- Traffic Logs ---
    public function trafficLogs(array $params): void
    {
        $id = (int)$params['id'];
        $days = min(90, max(1, (int)($_GET['days'] ?? 7)));
        $data = TrafficLog::chart($id, $days);
        $summary = TrafficLog::summary($id, $days);
        $this->json(['success' => true, 'data' => $data, 'summary' => $summary]);
    }

    // --- Summary ---
    public function summary(): void
    {
        $this->json(['success' => true, 'data' => Website::summary()]);
    }
}
