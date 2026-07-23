<?php
declare(strict_types=1);

// No cache headers
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');

if (isset($_GET['phpdebug'])) {
    header('Content-Type: text/plain');
    echo "Entry point reached\n";
    echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
    echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
    echo "BASE_PATH: " . (defined('BASE_PATH') ? BASE_PATH : 'not yet') . "\n";
    exit;
}

require_once __DIR__ . '/../bootstrap.php';

session_start();

// Skip CSRF check for API routes
$isApiRoute = str_contains($_SERVER['REQUEST_URI'], '/api/');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isApiRoute) {
    App\Middleware\CSRF::check();
}

if (!isset($_SESSION['_csrf_token'])) {
    App\Middleware\CSRF::generate();
}

$router = new App\Libraries\Router();

require_once BASE_PATH . '/routes/web.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
