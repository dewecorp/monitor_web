<?php
declare(strict_types=1);

namespace App\Models;

class Incident extends Model
{
    protected static string $table = 'incidents';

    public static function open(int $websiteId = 0): array
    {
        $where = "WHERE status IN ('open','investigating')";
        $params = [];
        if ($websiteId > 0) {
            $where .= " AND website_id = ?";
            $params[] = $websiteId;
        }
        $stmt = static::db()->prepare("SELECT i.*, w.nama_website, w.url FROM incidents i JOIN websites w ON w.id = i.website_id {$where} ORDER BY i.severity ASC, i.created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function createIfNotOpen(int $websiteId, string $title, string $description, string $severity = 'medium'): ?int
    {
        $existing = static::db()->prepare("SELECT id FROM incidents WHERE website_id = ? AND status IN ('open','investigating') AND title = ? LIMIT 1");
        $existing->execute([$websiteId, $title]);
        if ($existing->fetch()) return null;

        return static::insert([
            'website_id' => $websiteId,
            'title' => $title,
            'description' => $description,
            'severity' => $severity,
            'status' => 'open',
        ]);
    }

    public static function resolve(int $websiteId, string $title): bool
    {
        $stmt = static::db()->prepare("UPDATE incidents SET status = 'resolved', resolved_at = NOW() WHERE website_id = ? AND title = ? AND status IN ('open','investigating')");
        return $stmt->execute([$websiteId, $title]);
    }

    public static function recent(int $limit = 10): array
    {
        return static::db()->query("SELECT i.*, w.nama_website, w.url FROM incidents i JOIN websites w ON w.id = i.website_id ORDER BY i.created_at DESC LIMIT {$limit}")->fetchAll();
    }

    public static function countBySeverity(): array
    {
        return static::db()->query("SELECT severity, COUNT(*) as total FROM incidents WHERE status IN ('open','investigating') GROUP BY severity")->fetchAll();
    }
}
