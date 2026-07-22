<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', BASE_PATH . '/resources/views');
define('STORAGE_PATH', BASE_PATH . '/storage');

require_once BASE_PATH . '/vendor/autoload.php';

$dotenv = new App\Libraries\DotEnv(BASE_PATH . '/.env');
$dotenv->load();

error_reporting(env('APP_DEBUG', false) ? E_ALL : 0);
ini_set('display_errors', env('APP_DEBUG', false) ? '1' : '0');

date_default_timezone_set('Asia/Jakarta');

session_name(env('SESSION_NAME', 'webguardian_sess'));
session_set_cookie_params([
    'lifetime' => (int)env('SESSION_LIFETIME', 120) * 60,
    'path' => '/',
    'secure' => env('APP_ENV') === 'production',
    'httponly' => true,
    'samesite' => 'Lax',
]);

App\Middleware\SecurityHeaders::set();

function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $default;
}
