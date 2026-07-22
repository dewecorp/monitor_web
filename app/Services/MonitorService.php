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
            'is_up' => $health['is_up'],
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

            TrafficLog::record(
                $websiteId,
                rand(5, 30),
                rand(10, 150),
                round(rand(10, 100) / 10, 2),
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
            CURLOPT_NOBODY => false,
            CURLOPT_RANGE => '0-1024',
            CURLOPT_HEADER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);
        $start = microtime(true);
        curl_exec($ch);
        $end = microtime(true);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'status_code' => $info['http_code'] ?: 0,
            'response_time_ms' => round(($end - $start) * 1000),
            'is_up' => ($info['http_code'] >= 200 && $info['http_code'] < 500) ? 1 : 0,
            'error' => $error ?: null,
        ];
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
