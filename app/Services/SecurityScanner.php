<?php
declare(strict_types=1);

namespace App\Services;

class SecurityScanner
{
    private string $url;
    private array $headers = [];
    private array $result;

    public function __construct(string $url)
    {
        $this->url = rtrim($url, '/');
        $this->result = [
            'headers_secure' => 0,
            'has_xss_protection' => 0,
            'has_hsts' => 0,
            'has_csp' => 0,
            'has_referrer_policy' => 0,
            'has_permission_policy' => 0,
            'env_exposed' => 0,
            'git_exposed' => 0,
            'config_exposed' => 0,
            'backup_exposed' => 0,
            'directory_listing' => 0,
            'safe_browsing' => 1,
            'blacklisted' => 0,
            'score' => 0,
        ];
    }

    public function scan(): array
    {
        $this->fetchHeaders();
        $this->checkSecurityHeaders();
        $this->checkExposedFiles();
        $this->calculateScore();
        return $this->result;
    }

    private function fetchHeaders(): void
    {
        if (!preg_match('#^https?://#i', $this->url)) {
            $this->url = 'https://' . $this->url;
        }
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_RANGE => '0-1024',
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $headerSize = $info['header_size'] ?? 0;
        $headersRaw = substr($response, 0, $headerSize);
        foreach (explode("\r\n", $headersRaw) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $this->headers[strtolower(trim($key))] = trim($value);
            }
        }
    }

    private function checkSecurityHeaders(): void
    {
        $h = $this->headers;

        $this->result['has_xss_protection'] = (isset($h['x-xss-protection']) && str_contains($h['x-xss-protection'], '1')) ? 1 : 0;
        $this->result['has_hsts'] = isset($h['strict-transport-security']) ? 1 : 0;
        $this->result['has_csp'] = isset($h['content-security-policy']) ? 1 : 0;
        $this->result['has_referrer_policy'] = isset($h['referrer-policy']) ? 1 : 0;
        $this->result['has_permission_policy'] = isset($h['permissions-policy']) ? 1 : 0;

        $secureHeaders = $this->result['has_xss_protection']
            || isset($h['x-content-type-options'])
            || isset($h['x-frame-options']);
        $this->result['headers_secure'] = $secureHeaders ? 1 : 0;
    }

    private function checkExposedFiles(): void
    {
        $paths = [
            '.env' => 'env_exposed',
            '.git/HEAD' => 'git_exposed',
            'config/' => 'config_exposed',
            'backup/' => 'backup_exposed',
        ];

        foreach ($paths as $path => $key) {
            $testUrl = rtrim($this->url, '/') . '/' . $path;
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $testUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_NOBODY => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code >= 200 && $code < 400 && $code !== 403) {
                $this->result[$key] = 1;
            }
        }

        // Check directory listing
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url . '/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body && $code >= 200 && (str_contains($body, 'Index of') || str_contains($body, '<title>Index'))) {
            $this->result['directory_listing'] = 1;
        }
    }

    private function calculateScore(): void
    {
        $score = 0;
        $hasAnyResponse = !empty($this->headers);

        // Baseline: server reachable & SSL active
        if ($hasAnyResponse) $score += 20;

        // HTTPS enforcement
        if ($this->result['has_hsts']) $score += 20;

        // Anti-XSS & Content Security
        if ($this->result['has_xss_protection']) $score += 12;
        if ($this->result['has_csp']) $score += 12;

        // Policy headers
        if ($this->result['headers_secure']) $score += 10;
        if ($this->result['has_referrer_policy']) $score += 8;
        if ($this->result['has_permission_policy']) $score += 8;

        // Exposure - bonus if clean
        if ($hasAnyResponse && !$this->result['env_exposed']) $score += 2;
        if ($hasAnyResponse && !$this->result['git_exposed']) $score += 2;
        if ($hasAnyResponse && !$this->result['config_exposed']) $score += 2;
        if ($hasAnyResponse && !$this->result['backup_exposed']) $score += 2;
        if ($hasAnyResponse && !$this->result['directory_listing']) $score += 2;

        if (!$hasAnyResponse) $score = 0;

        $this->result['score'] = max(10, min($score, 100));
    }
}
