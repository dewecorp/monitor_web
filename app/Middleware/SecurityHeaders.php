<?php
declare(strict_types=1);

namespace App\Middleware;

class SecurityHeaders
{
    public static function set(): void
    {
        $headers = [
            "X-Frame-Options: DENY",
            "X-Content-Type-Options: nosniff",
            "Cache-Control: no-store, no-cache, must-revalidate",
        ];
        foreach ($headers as $h) {
            header($h);
        }
    }
}
