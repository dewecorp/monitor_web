<?php
declare(strict_types=1);

namespace App\Middleware;

class Auth
{
    public static function check(): void
    {
        if (!isset($_SESSION['user_id'])) {
            redirect('/login');
        }
    }

    public static function guest(): void
    {
        if (isset($_SESSION['user_id'])) {
            redirect('/');
        }
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
