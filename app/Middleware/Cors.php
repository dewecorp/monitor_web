<?php
declare(strict_types=1);

namespace App\Middleware;

class Cors
{
    public static function handle(): void
    {
        header("Access-Control-Allow-Origin: " . env('APP_URL', '*'));
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN");
        header("Access-Control-Allow-Credentials: true");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
