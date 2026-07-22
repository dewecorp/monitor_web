<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\WebsiteController;
use App\Controllers\MonitorController;
use App\Controllers\SecurityController;

$router->get('/', [DashboardController::class, 'index'], 'dashboard');
$router->get('/dashboard', [DashboardController::class, 'index'], 'dashboard');

$router->get('/login', [AuthController::class, 'login'], 'login');
$router->post('/login', [AuthController::class, 'doLogin']);
$router->get('/logout', [AuthController::class, 'logout'], 'logout');

$router->get('/websites', [WebsiteController::class, 'index'], 'websites');
$router->get('/websites/create', [WebsiteController::class, 'create'], 'websites.create');
$router->post('/websites/store', [WebsiteController::class, 'store']);
$router->get('/websites/{id}/edit', [WebsiteController::class, 'edit']);
$router->post('/websites/{id}/update', [WebsiteController::class, 'update']);
$router->post('/websites/{id}/delete', [WebsiteController::class, 'destroy']);

$router->get('/monitor/health', [MonitorController::class, 'health'], 'monitor.health');
$router->get('/monitor/health/{id}', [MonitorController::class, 'healthDetail']);
$router->get('/monitor/security', [SecurityController::class, 'index'], 'monitor.security');
$router->get('/monitor/security/{id}', [SecurityController::class, 'detail']);
$router->get('/monitor/traffic', [MonitorController::class, 'traffic'], 'monitor.traffic');
$router->get('/monitor/traffic/{id}', [MonitorController::class, 'trafficDetail']);

// Settings & Reports
$router->get('/settings', [App\Controllers\SettingsController::class, 'index']);
$router->post('/settings/update', [App\Controllers\SettingsController::class, 'update']);
$router->post('/settings/credentials', [App\Controllers\SettingsController::class, 'credentials']);
$router->get('/settings/test-notification', [App\Controllers\SettingsController::class, 'testNotification']);
$router->get('/reports', [App\Controllers\ReportController::class, 'index']);
$router->get('/reports/export-csv', [App\Controllers\ReportController::class, 'exportCsv']);
$router->get('/notifications', [App\Controllers\ReportController::class, 'notificationHistory']);
$router->get('/notifications/mark-read', [App\Controllers\ReportController::class, 'markRead']);
$router->get('/notifications/mark-all-read', [App\Controllers\ReportController::class, 'markAllRead']);

// API Key management
$router->get('/settings/api-keys', [App\Controllers\ApiKeyController::class, 'index']);
$router->post('/settings/api-keys/generate', [App\Controllers\ApiKeyController::class, 'generate']);
$router->post('/settings/api-keys/revoke', [App\Controllers\ApiKeyController::class, 'revoke']);

// REST API
$router->get('/api/summary', [App\Controllers\ApiController::class, 'summary']);
$router->get('/api/websites', [App\Controllers\ApiController::class, 'websites']);
$router->get('/api/websites/{id}', [App\Controllers\ApiController::class, 'websiteDetail']);
$router->get('/api/websites/{id}/monitor', [App\Controllers\ApiController::class, 'monitorLogs']);
$router->get('/api/websites/{id}/security', [App\Controllers\ApiController::class, 'securityLogs']);
$router->get('/api/websites/{id}/ssl', [App\Controllers\ApiController::class, 'sslLogs']);
$router->get('/api/websites/{id}/traffic', [App\Controllers\ApiController::class, 'trafficLogs']);

// AI Security Analysis
$router->get('/ai-analysis', [App\Controllers\AiAnalysisController::class, 'index']);

// Server Hardening
$router->get('/hardening', [App\Controllers\HardeningController::class, 'index']);

// Incident Response
$router->get('/incident-response', [App\Controllers\IncidentController::class, 'index']);

// Vulnerability Scanner
$router->get('/vulnerability-scan', [App\Controllers\VulnController::class, 'index']);
$router->get('/vulnerability-scan/api-scan', [App\Controllers\VulnController::class, 'apiScan']);

// Threat Detection
$router->get('/threat-detection', [App\Controllers\ThreatController::class, 'index']);
$router->get('/threat-detection/api-scan', [App\Controllers\ThreatController::class, 'apiScan']);

// Internal API (session auth)
// Security Scan
$router->get('/security-scan', [App\Controllers\SecurityScanController::class, 'index']);
$router->post('/security-scan/run', [App\Controllers\SecurityScanController::class, 'run']);
$router->get('/security-scan/api-scan', [App\Controllers\SecurityScanController::class, 'apiScan']);
$router->post('/security-scan/quarantine', [App\Controllers\SecurityScanController::class, 'quarantine']);

// File Integrity Monitor
$router->get('/file-integrity', [App\Controllers\FileIntegrityController::class, 'index']);
$router->post('/file-integrity/scan', [App\Controllers\FileIntegrityController::class, 'scan']);
$router->post('/file-integrity/mark-reviewed', [App\Controllers\FileIntegrityController::class, 'markReviewed']);

// Monitoring Agent
$router->post('/api/agent/report', [App\Controllers\AgentController::class, 'report']);
$router->get('/agent/servers', [App\Controllers\AgentController::class, 'servers']);
$router->post('/agent/restart', [App\Controllers\AgentController::class, 'restartService']);

$router->get('/api/check-all', [MonitorController::class, 'apiCheckAll']);
$router->get('/api/check/{id}', [MonitorController::class, 'apiCheckSingle']);
$router->get('/api/traffic/{id}', [MonitorController::class, 'apiTrafficData']);
$router->get('/api/chart-data', [DashboardController::class, 'apiChartData']);

// Redirect old paths
$router->get('/login.php', function() { redirect('/login'); });
$router->get('/websites.php', function() { redirect('/websites'); });
$router->get('/health.php', function() { redirect('/monitor/health'); });
$router->get('/security.php', function() { redirect('/monitor/security'); });
