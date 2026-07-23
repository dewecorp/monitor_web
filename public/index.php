<?php
declare(strict_types=1);

// No cache headers + force refresh marker
$v = time();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
header("X-WG-Version: {$v}");

// Security headers (fallback if .htaccess not applied)
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

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
