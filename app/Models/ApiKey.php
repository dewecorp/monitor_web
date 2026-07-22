<?php
declare(strict_types=1);

namespace App\Models;

class ApiKey extends Model
{
    protected static string $table = 'api_keys';

    public static function generate(int $userId, string $name, string $perms = 'read'): string
    {
        $key = 'wg_' . bin2hex(random_bytes(32));
        static::insert([
            'user_id' => $userId,
            'name' => $name,
            'key' => $key,
            'permissions' => $perms,
        ]);
        return $key;
    }

    public static function validate(string $key): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM api_keys WHERE `key` = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        if ($result) {
            static::db()->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?")->execute([$result['id']]);
        }
        return $result ?: null;
    }

    public static function forUser(int $userId): array
    {
        $stmt = static::db()->prepare("SELECT * FROM api_keys WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function revoke(int $id, int $userId): bool
    {
        $stmt = static::db()->prepare("UPDATE api_keys SET is_active = 0 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}
