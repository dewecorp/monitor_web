<?php
declare(strict_types=1);

namespace App\Middleware;

class CSRF
{
    public static function generate(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token'] = $token;
        $_SESSION['_csrf_time'] = time();
        return $token;
    }

    public static function validate(?string $token): bool
    {
        $stored = $_SESSION['_csrf_token'] ?? '';
        $time = $_SESSION['_csrf_time'] ?? 0;

        if (!$token || !$stored) return false;
        if (!hash_equals($stored, $token)) return false;

        // Token expires after 2 hours
        if (time() - $time > 7200) return false;

        return true;
    }

    public static function check(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!self::validate($token)) {
                http_response_code(419);
                echo json_encode(['success' => false, 'error' => 'CSRF token invalid or expired']);
                exit;
            }
            self::generate();
        }
    }
}
