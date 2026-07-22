<?php
declare(strict_types=1);

namespace App\Models;

class SecurityLog extends Model
{
    protected static string $table = 'security_logs';

    public static function latestForWebsite(int $websiteId): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM security_logs WHERE website_id = ? ORDER BY checked_at DESC LIMIT 1");
        $stmt->execute([$websiteId]);
        return $stmt->fetch() ?: null;
    }

    public static function runScan(int $websiteId, string $url): array
    {
        require_once APP_PATH . '/Services/SecurityScanner.php';
        $scanner = new \App\Services\SecurityScanner($url);
        $result = $scanner->scan();

        $stmt = static::db()->prepare("
            INSERT INTO security_logs
            (website_id, headers_secure, has_xss_protection, has_hsts, has_csp,
             has_referrer_policy, has_permission_policy,
             env_exposed, git_exposed, config_exposed, backup_exposed,
             directory_listing, safe_browsing, blacklisted, score)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $websiteId,
            $result['headers_secure'],
            $result['has_xss_protection'],
            $result['has_hsts'],
            $result['has_csp'],
            $result['has_referrer_policy'],
            $result['has_permission_policy'],
            $result['env_exposed'],
            $result['git_exposed'],
            $result['config_exposed'],
            $result['backup_exposed'],
            $result['directory_listing'],
            $result['safe_browsing'],
            $result['blacklisted'],
            $result['score'],
        ]);

        return $result;
    }
}
