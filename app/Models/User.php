<?php
declare(strict_types=1);

namespace App\Models;

class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';

    public static function findByUsername(string $username): ?array
    {
        return static::first('username', $username);
    }

    public static function findByEmail(string $email): ?array
    {
        return static::first('email', $email);
    }

    public static function logActivity(int $userId, string $aksi, ?string $detail = null): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        static::db()->prepare("INSERT INTO activity_logs (user_id, aksi, detail, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)")
            ->execute([$userId, $aksi, $detail, $ip, $ua]);
    }

    public static function logLogin(int $userId, string $status, ?string $reason = null): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        static::db()->prepare("INSERT INTO login_history (user_id, ip_address, user_agent, status, failed_reason) VALUES (?, ?, ?, ?, ?)")
            ->execute([$userId, $ip, $ua, $status, $reason]);
    }

    public static function updateLogin(int $userId, string $ip): void
    {
        static::db()->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?")
            ->execute([$ip, $userId]);
    }

    public static function recentActivity(int $limit = 10): array
    {
        return static::db()->query("SELECT al.*, u.nama FROM activity_logs al LEFT JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC LIMIT {$limit}")->fetchAll();
    }
}
