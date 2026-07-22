<?php
declare(strict_types=1);

namespace App\Middleware;

class CSRF
{
    public static function generate(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token'] = $token;
        return $token;
    }

    public static function validate(?string $token): bool
    {
        $stored = $_SESSION['_csrf_token'] ?? '';
        if (!$token || !$stored) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    public static function check(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!self::validate($token)) {
                http_response_code(419);
                die('CSRF token mismatch');
            }
            self::generate();
        }
    }
}
