<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Models\Website;
use App\Models\TrafficLog;

/**
 * Integrasi Google Analytics Data API (GA4) — tanpa library eksternal.
 * Autentikasi memakai Service Account (OAuth2 JWT Bearer flow).
 *
 * Pengaturan yang dibutuhkan:
 *   - Per website (opsional): kolom ga_property_id, ga_client_email, ga_private_key
 *   - Global (fallback): setting ga_client_email & ga_private_key via halaman Settings
 * Jika website punya kredensial sendiri, kredensial itu yang dipakai;
 * jika kosong, dipakai kredensial global.
 */
class GoogleAnalytics
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const API_URL = 'https://analyticsdata.googleapis.com/v1beta/properties/%s:runReport';
    private const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    private ?string $accessToken = null;
    private ?string $lastError = null;
    private string $credKey = '';

    public function isConfigured(): bool
    {
        return $this->resolveCredentials(null) !== null;
    }

    /**
     * Ambil kredensial efektif: milik website jika diisi, jika tidak pakai global.
     * $website = array baris websites (boleh null untuk cek global saja).
     */
    private function resolveCredentials(?array $website): ?array
    {
        $email = trim((string)($website['ga_client_email'] ?? ''));
        $key = (string)($website['ga_private_key'] ?? '');

        if ($email === '' || trim($key) === '') {
            $email = trim((string)Setting::get('ga_client_email', ''));
            $key = (string)Setting::get('ga_private_key', '');
        }

        $key = str_replace('\\n', "\n", $key);
        if ($email === '' || trim($key) === '') return null;

        return ['email' => $email, 'key' => $key];
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private static function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Tukar JWT service account menjadi access token OAuth2.
     * Kredensial per-website didahulukan; token di-cache per set kredensial.
     */
    private function getAccessToken(?array $website = null): ?string
    {
        $creds = $this->resolveCredentials($website);
        if ($creds === null) {
            $this->lastError = 'Kredensial Google Analytics belum diisi (baik per website maupun global).';
            return null;
        }

        $cacheKey = md5($creds['email'] . '|' . $creds['key']);
        if ($this->accessToken !== null && $this->credKey === $cacheKey) {
            return $this->accessToken;
        }

        $now = time();
        $header = self::base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = self::base64Url(json_encode([
            'iss' => $creds['email'],
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signature = '';
        if (!openssl_sign("{$header}.{$claims}", $signature, $creds['key'], OPENSSL_ALGO_SHA256)) {
            $this->lastError = 'Private key tidak valid — gagal membuat signature JWT.';
            return null;
        }
        $jwt = "{$header}.{$claims}." . self::base64Url($signature);

        $response = $this->httpPost(self::TOKEN_URL, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]), ['Content-Type: application/x-www-form-urlencoded']);

        if (empty($response['access_token'])) {
            $this->lastError = 'Gagal mendapatkan access token: ' . ($response['error_description'] ?? $response['error'] ?? 'respons kosong dari Google');
            return null;
        }

        $this->accessToken = $response['access_token'];
        $this->credKey = $cacheKey;
        return $this->accessToken;
    }

    /**
     * Ambil metrik traffic dari GA4 untuk satu properti dan satu tanggal.
     * $website = baris websites untuk memakai kredensial khusus miliknya (boleh null = global).
     * Return: ['visitors'=>int, 'page_views'=>int, 'bandwidth_mb'=>float, 'avg_response_ms'=>float] atau null jika gagal.
     */
    public function fetchReport(string $propertyId, string $date = 'yesterday', ?array $website = null): ?array
    {
        $token = $this->getAccessToken($website);
        if (!$token) return null;

        $propertyId = preg_replace('/[^0-9]/', '', $propertyId);
        if ($propertyId === '') {
            $this->lastError = 'GA Property ID tidak valid.';
            return null;
        }

        $body = json_encode([
            'dateRanges' => [['startDate' => $date, 'endDate' => $date]],
            'metrics' => [
                ['name' => 'totalUsers'],
                ['name' => 'screenPageViews'],
                ['name' => 'averageSessionDuration'],
            ],
        ]);

        $response = $this->httpPost(
            sprintf(self::API_URL, $propertyId),
            $body,
            ['Content-Type: application/json', 'Authorization: Bearer ' . $token]
        );

        if (isset($response['error'])) {
            $this->lastError = $response['error']['message'] ?? 'Error dari GA4 API';
            return null;
        }

        if (empty($response['rows'][0]['metricValues'])) {
            // Tidak ada traffic pada tanggal itu — ini hasil valid, bukan error
            return ['visitors' => 0, 'page_views' => 0, 'bandwidth_mb' => 0.0, 'avg_response_ms' => 0.0];
        }

        $metrics = $response['rows'][0]['metricValues'];
        return [
            'visitors' => (int)($metrics[0]['value'] ?? 0),
            'page_views' => (int)($metrics[1]['value'] ?? 0),
            'bandwidth_mb' => 0.0, // GA4 tidak menyediakan metrik bandwidth
            'avg_response_ms' => round((float)($metrics[2]['value'] ?? 0) * 1000), // detik -> ms (durasi sesi rata-rata)
        ];
    }

    /**
     * Sinkronisasi data GA kemarin ke traffic_logs untuk semua website
     * yang sudah diisi ga_property_id. Return ringkasan hasil.
     */
    public function syncAllWebsites(): array
    {
        $result = ['synced' => 0, 'skipped' => 0, 'errors' => []];

        $websites = Website::active();
        foreach ($websites as $website) {
            $propertyId = trim((string)($website['ga_property_id'] ?? ''));
            if ($propertyId === '') {
                $result['skipped']++;
                continue;
            }

            $data = $this->fetchReport($propertyId, 'yesterday', $website);
            if ($data === null) {
                $result['errors'][] = ($website['nama_website'] ?? "ID {$website['id']}") . ': ' . ($this->lastError ?? 'unknown error');
                continue;
            }

            TrafficLog::recordForDate(
                (int)$website['id'],
                date('Y-m-d', strtotime('yesterday')),
                $data['visitors'],
                $data['page_views'],
                $data['bandwidth_mb'],
                $data['avg_response_ms']
            );
            $result['synced']++;
        }

        return $result;
    }

    /**
     * Test koneksi: autentikasi + query 7 hari terakhir ke satu properti.
     * $overrideCreds untuk menguji kredensial yang belum disimpan (dari form).
     */
    public function testConnection(?string $propertyId = null, ?array $overrideCreds = null): array
    {
        $website = null;
        if ($overrideCreds && !empty($overrideCreds['ga_client_email']) && !empty($overrideCreds['ga_private_key'])) {
            $website = $overrideCreds;
        }

        if ($this->resolveCredentials($website) === null) {
            return ['success' => false, 'message' => 'Kredensial GA belum diisi. Simpan dulu Client Email dan Private Key.'];
        }

        $token = $this->getAccessToken($website);
        if (!$token) {
            return ['success' => false, 'message' => 'Autentikasi gagal: ' . $this->lastError];
        }

        if (!$propertyId) {
            return ['success' => true, 'message' => 'Autentikasi ke Google berhasil. Isi GA Property ID untuk mengambil data.'];
        }

        $data = $this->fetchReport($propertyId, '7daysAgo', $website);
        if ($data === null) {
            return ['success' => false, 'message' => 'Autentikasi OK, tapi query gagal: ' . $this->lastError];
        }

        return [
            'success' => true,
            'message' => "Koneksi berhasil. Contoh data 7 hari lalu: {$data['visitors']} pengunjung, {$data['page_views']} page views.",
        ];
    }

    private function httpPost(string $url, string $body, array $headers): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            $this->lastError = 'cURL error: ' . curl_error($ch);
            curl_close($ch);
            return [];
        }
        curl_close($ch);
        return json_decode($raw, true) ?: [];
    }
}
