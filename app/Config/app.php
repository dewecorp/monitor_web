<?php
declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'WEBGUARDIAN'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'Asia/Jakarta',
    'locale' => 'id',
    'session_lifetime' => (int)env('SESSION_LIFETIME', 120),
    'session_name' => env('SESSION_NAME', 'webguardian_sess'),
    'cron_token' => env('CRON_TOKEN', ''),
];
