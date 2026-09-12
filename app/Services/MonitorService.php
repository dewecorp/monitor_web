<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\MonitorLog;
use App\Models\SecurityLog;
use App\Models\SslLog;
use App\Models\TrafficLog;

class MonitorService
{
    public function checkWebsite(int $websiteId, string $url): array
    {
        $health = $this->healthCheck($url);
        MonitorLog::insert([
            'website_id' => $websiteId,
            'status_code' => $health['status_code'],
            'response_time_ms' => $health['response_time_ms'],
            'is_up' => (int)$health['is_up'],
            'is_blocked' => (int)($health['blocked'] ?? 0),
            'error_message' => $health['error'],
        ]);

        if ($health['is_up']) {
            $scanner = new SecurityScanner($url);
            $security = $scanner->scan();
            SecurityLog::insert(array_merge(
                ['website_id' => $websiteId],
                $security
            ));

            $ssl = $this->sslCheck($url);
            if ($ssl) {
                SslLog::insert(array_merge(
                    ['website_id' => $websiteId],
                    $ssl
                ));
            }

            // Catat data traffic nyata. Pengunjung/page views diambil dari
            // Google Analytics jika website sudah diisi ga_property_id.
            // Jika belum dikonfigurasi, hanya response time health check yang dicatat.
            $gaData = $this->fetchGoogleAnalytics($websiteId);
            TrafficLog::record(
                $websiteId,
                $gaData['visitors'] ?? 0,
                $gaData['page_views'] ?? 0,
                $gaData['bandwidth_mb'] ?? 0.0,
                $health['response_time_ms']
            );
        }

        return $health;
    }

    public function healthCheck(string $url): array
    {
        $ch = curl_init();
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
                'Upgrade-Insecure-Requests: 1',
            ],
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);
        $start = microtime(true);
        $raw = (string)curl_exec($ch);
        $end = microtime(true);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);

        $headerSize = (int)($info['header_size'] ?? 0);
        $headerBlock = substr($raw, 0, $headerSize);
        $statusCode = (int)($info['http_code'] ?: 0);
        $isUp = ($statusCode >= 200 && $statusCode < 400) ? 1 : 0;
        $errMsg = $error ?: null;
        $blocked = false;

        // Cloudflare kadang memblokir IP server monitoring (403 + cf-ray)
        // padahal situsnya sehat. Itu bukan situs down — tandai terblokir.
        if ($this->isCloudflareBlock($statusCode, $headerBlock)) {
            $blocked = true;
            $errMsg = 'Terblokir Cloudflare (HTTP 403) — IP server dibatasi, bukan situs down';
        }

        return [
            'status_code' => $statusCode,
            'response_time_ms' => round(($end - $start) * 1000),
            'is_up' => $isUp,
            'blocked' => $blocked,
            'error' => $errMsg ?: ($isUp ? null : "HTTP {$statusCode}"),
        ];
    }

    private function isCloudflareBlock(int $statusCode, string $headerBlock): bool
    {
        if ($statusCode !== 403) return false;
        $lower = strtolower($headerBlock);
        return str_contains($lower, 'cf-ray') && str_contains($lower, 'cloudflare');
    }

    public function sslCheck(string $url): ?array
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) return null;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CERTINFO => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
        ]);
        curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (empty($info['certinfo'])) return null;

        $cert = $info['certinfo'];
        $expireDate = $cert['Expire date'] ?? null;
        $issuer = $cert['Issuer']['O'] ?? $cert['Issuer']['CN'] ?? 'Unknown';

        return [
            'ssl_valid' => ($info['ssl_verify_result'] === 0) ? 1 : 0,
            'ssl_issuer' => $issuer,
            'ssl_expires' => $expireDate ? date('Y-m-d', strtotime($expireDate)) : null,
            'ssl_remaining_days' => $expireDate ? max(0, (int)((strtotime($expireDate) - time()) / 86400)) : 0,
            'tls_version' => $info['protocol'] ?? null,
        ];
    }

    /**
     * Ambil data traffic GA4 untuk website jika ga_property_id sudah diisi
     * dan kredensial GA sudah dikonfigurasi. Return null jika tidak tersedia.
     */
    private function fetchGoogleAnalytics(int $websiteId): ?array
    {
        $website = \App\Models\Website::find($websiteId);
        $propertyId = trim((string)($website['ga_property_id'] ?? ''));
        if ($propertyId === '') return null;

        $ga = new GoogleAnalytics();
        return $ga->fetchReport($propertyId, 'today', $website);
    }

    public function checkAll(): array
    {
        $websites = \App\Models\Website::active();
        $checked = 0;
        $errors = 0;

        foreach ($websites as $website) {
            $health = $this->checkWebsite((int)$website['id'], $website['url']);
            $health['is_up'] ? $checked++ : $errors++;
        }

        return ['checked' => $checked, 'errors' => $errors];
    }
}
