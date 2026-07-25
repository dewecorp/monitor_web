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

        if (!$token || !$stored) {
            error_log("CSRF: empty token - stored=" . (empty($stored) ? 'empty' : 'set') . " submitted=" . (empty($token) ? 'empty' : 'set'));
            return false;
        }
        if (!hash_equals($stored, $token)) {
            error_log("CSRF: mismatch - stored=" . substr($stored, 0, 10) . "... submitted=" . substr($token, 0, 10) . "...");
            return false;
        }

        // Token expires after 2 hours
        if (time() - $time > 7200) {
            error_log("CSRF: expired - time=" . $time . " now=" . time());
            return false;
        }

        return true;
    }

    public static function check(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!self::validate($token)) {
                $_SESSION['error'] = 'CSRF token mismatch. Silakan refresh halaman dan coba lagi.';
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                header('Location: ' . $referer);
                exit;
            }
        }
    }
}
