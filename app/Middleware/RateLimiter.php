<?php
declare(strict_types=1);

namespace App\Middleware;

class RateLimiter
{
    private static array $limits = [];

    public static function limit(string $key, int $maxAttempts = 5, int $period = 60): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $storageKey = "{$key}:{$ip}";
        $now = time();

        if (!isset(self::$limits[$storageKey])) {
            self::$limits[$storageKey] = ['attempts' => 0, 'reset' => $now + $period];
        }

        $record = &self::$limits[$storageKey];

        if ($record['reset'] <= $now) {
            $record['attempts'] = 0;
            $record['reset'] = $now + $period;
        }

        $record['attempts']++;

        if ($record['attempts'] > $maxAttempts) {
            http_response_code(429);
            $retryAfter = $record['reset'] - $now;
            header("Retry-After: {$retryAfter}");
            jsonResponse([
                'success' => false,
                'message' => 'Too many requests. Try again in ' . $retryAfter . ' seconds.',
            ], 429);
        }
    }
}
