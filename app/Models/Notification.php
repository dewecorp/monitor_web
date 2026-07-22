<?php
declare(strict_types=1);

namespace App\Models;

class Notification extends Model
{
    protected static string $table = 'notifications';

    public static function unread(int $limit = 20): array
    {
        return static::db()->query("SELECT n.*, w.nama_website FROM notifications n LEFT JOIN websites w ON w.id = n.website_id WHERE n.is_read = 0 ORDER BY n.created_at DESC LIMIT {$limit}")->fetchAll();
    }

    public static function markRead(int $id): bool
    {
        return static::update($id, ['is_read' => 1]);
    }

    public static function markAllRead(): bool
    {
        return (bool)static::db()->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    }

    public static function create(int $websiteId, string $type, string $channel, string $title, string $message): int
    {
        return static::insert([
            'website_id' => $websiteId,
            'type' => $type,
            'channel' => $channel,
            'title' => $title,
            'message' => $message,
        ]);
    }

    public static function sendTelegram(string $message): bool
    {
        $token = env('TELEGRAM_BOT_TOKEN', '');
        $chatId = env('TELEGRAM_CHAT_ID', '');
        if (!$token || !$chatId) return false;

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $data = ['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'HTML'];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    public static function sendDiscord(string $message): bool
    {
        $webhook = env('DISCORD_WEBHOOK', '');
        if (!$webhook) return false;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $webhook,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['content' => $message]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 204;
    }
}
