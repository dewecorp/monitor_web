<?php
declare(strict_types=1);

namespace App\Middleware;

class SecurityHeaders
{
    public static function set(): void
    {
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

        if (env('APP_ENV') === 'production') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' data:; connect-src 'self'");
        }
    }
}
