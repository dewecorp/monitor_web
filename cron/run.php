<?php
/**
 * WEBGUARDIAN Cron Engine
 *
 * Usage:
 *   CLI:  php cron/run.php
 *   HTTP: http://localhost/monitor_web/cron/run.php?token=your_token
 *
 * Setup Windows Task Scheduler (every 1 minute):
 *   php D:\laragon\www\monitor_web\cron\run.php
 *
 * Setup Linux crontab (every 1 minute):
 *   * * * * * /usr/bin/php /var/www/monitor_web/cron/run.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$isHttp = php_sapi_name() !== 'cli';

if ($isHttp) {
    $token = $_GET['token'] ?? '';
    $cronToken = env('CRON_TOKEN', '');
    if ($token !== $cronToken) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }
}

$startTime = microtime(true);

$engine = new App\Services\CronEngine();

try {
    $results = $engine->run();
    $elapsed = round((microtime(true) - $startTime) * 1000, 2);

    $results['duration_ms'] = $elapsed;
    $results['time'] = date('Y-m-d H:i:s');

    if ($isHttp) {
        header('Content-Type: application/json');
        echo json_encode($results, JSON_PRETTY_PRINT);
    } else {
        echo "WEBGUARDIAN Cron Run: {$results['time']}\n";
        echo "Duration: {$elapsed}ms\n";
        echo "Checked: {$results['checked']} | Errors: {$results['errors']}\n";
        echo "Incidents: {$results['incidents_created']} new / {$results['incidents_resolved']} resolved\n";
        echo "Notifications: {$results['notifications_sent']}\n";
        foreach ($results['details'] as $d) {
            echo "  - {$d['website']}: {$d['status']} ({$d['http_code']}, {$d['response_ms']}ms)\n";
        }
    }
} catch (\Throwable $e) {
    if ($isHttp) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } else {
        echo "CRON ERROR: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
}
