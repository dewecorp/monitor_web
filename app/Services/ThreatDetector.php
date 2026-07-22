<?php
declare(strict_types=1);

namespace App\Services;

class ThreatDetector
{
    private string $url;
    private string $html = '';
    private array $headers = [];
    private int $httpCode = 0;
    private array $threats = [];
    private int $threatScore = 100;

    public function __construct(string $url)
    {
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        $this->url = $url;
    }

    public function scan(): array
    {
        $this->fetchPage();
        $this->checkDefacement();
        $this->checkRedirect();
        $this->checkSeoSpam();
        $this->checkHiddenRedirect();
        $this->checkCryptoMiner();
        $this->checkPhishing();
        $this->checkIframeInjection();
        $this->calculateScore();

        return [
            'url' => $this->url,
            'http_code' => $this->httpCode,
            'threats' => $this->threats,
            'threat_count' => count($this->threats),
            'threat_score' => $this->threatScore,
            'severity' => $this->getSeverity(),
        ];
    }

    private function fetchPage(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
            ],
        ]);
        $response = curl_exec($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($response === false) return;

        $this->html = substr($response, $headerSize);
        $rawHeaders = substr($response, 0, $headerSize);
        foreach (explode("\r\n", $rawHeaders) as $line) {
            if (strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                $this->headers[strtolower(trim($k))] = trim($v);
            }
        }
    }

    private function checkDefacement(): void
    {
        if ($this->httpCode !== 200 && $this->httpCode !== 301 && $this->httpCode !== 302) {
            $this->addThreat('defacement', 'Website tidak merespon dengan normal', 'HTTP ' . $this->httpCode, 'high');
            return;
        }

        if (empty($this->html)) {
            $this->addThreat('defacement', 'Halaman kosong — kemungkinan defacement', 'Empty response body', 'critical');
            return;
        }

        $title = '';
        if (preg_match('/<title>([^<]*)<\/title>/i', $this->html, $m)) {
            $title = trim($m[1]);
        }

        $defaceKeywords = ['hacked', 'defaced', 'owned by', 'hak3d', 'cracked', 'pwned', 'h4ck3d', 'ganteng'];
        foreach ($defaceKeywords as $kw) {
            if (stripos($this->html, $kw) !== false) {
                $this->addThreat('defacement', "Kata kunci defacement ditemukan: '{$kw}'", "Title: {$title}", 'critical');
                break;
            }
        }

        if (strlen($this->html) < 100) {
            $this->addThreat('defacement', 'Halaman terlalu pendek (' . strlen($this->html) . ' bytes)', "Title: {$title}", 'medium');
        }
    }

    private function checkRedirect(): void
    {
        $redirectCodes = [301, 302, 303, 307, 308];
        if (in_array($this->httpCode, $redirectCodes)) {
            $location = $this->headers['location'] ?? $this->headers['Location'] ?? '';
            $parsedUrl = parse_url($location);
            $parsedBase = parse_url($this->url);

            if ($location && $parsedUrl && $parsedBase) {
                $targetHost = $parsedUrl['host'] ?? '';
                $baseHost = $parsedBase['host'] ?? '';

                if ($targetHost && $targetHost !== $baseHost && !str_contains($baseHost, $targetHost)) {
                    $this->addThreat('redirect', "Redirect mencurigakan ke domain lain", "{$this->url} → {$location}", 'high');
                } elseif ($targetHost && $targetHost !== $baseHost) {
                    $this->addThreat('redirect', "Redirect ke domain eksternal", "{$this->url} → {$location}", 'medium');
                }
            }
        }
    }

    private function checkSeoSpam(): void
    {
        if (empty($this->html)) return;

        $patterns = [
            '/<div[^>]*style\s*=\s*["\'](display\s*:\s*none|visibility\s*:\s*hidden|position\s*:\s*absolute[^;]*left\s*:\s*-\d+)["\'][^>]*>.*?<a\s+href/is' => ['Hidden spam link', 'medium'],
            '/<a[^>]*style\s*=\s*["\']display\s*:\s*none["\'][^>]*>/is' => ['Hidden anchor tag (SEO spam)', 'high'],
            '/<span[^>]*style\s*=\s*["\']font-size\s*:\s*0["\'][^>]*>/is' => ['Zero font-size (SEO spam)', 'high'],
            '/(viagra|casino|cialis|klik|sabu|gacor|slot|poker|togel)[^\s]{0,5}\s*(viagra|casino|cialis)/i' => ['Kata kunci spam farmasi/judi', 'medium'],
        ];

        foreach ($patterns as $pattern => [$desc, $sev]) {
            if (preg_match($pattern, $this->html)) {
                $this->addThreat('seo_spam', $desc, 'Pola terdeteksi di source', $sev);
            }
        }

        $totalLinks = substr_count($this->html, '<a ');
        $totalHidden = preg_match_all('/<div[^>]*style\s*=\s*["\'](display\s*:\s*none|visibility\s*:\s*hidden)[^>]*>/i', $this->html);
        if ($totalHidden > $totalLinks * 0.3 && $totalHidden > 3) {
            $this->addThreat('seo_spam', "{$totalHidden} hidden div ditemukan — kemungkinan cloaking", "Ratio hidden/visible: tinggi", 'high');
        }
    }

    private function checkHiddenRedirect(): void
    {
        if (empty($this->html)) return;

        $jsRedirectPatterns = [
            '/window\s*\.\s*location\s*=\s*["\']https?:\/\/(?!' . preg_quote(parse_url($this->url, PHP_URL_HOST) ?? '', '/') . ')/i' => ['JavaScript redirect ke domain eksternal', 'high'],
            '/window\s*\.\s*location\s*\.\s*href\s*=\s*["\']https?:\/\/(?!' . preg_quote(parse_url($this->url, PHP_URL_HOST) ?? '', '/') . ')/i' => ['JS location.href redirect ke domain lain', 'high'],
            '/window\s*\.\s*location\s*\.\s*replace\s*\(["\']https?:\/\/(?!' . preg_quote(parse_url($this->url, PHP_URL_HOST) ?? '', '/') . ')/i' => ['JS location.replace ke domain eksternal', 'high'],
            '/setTimeout\s*\([^)]*window\s*\.\s*location/i' => ['Delayed redirect via setTimeout', 'medium'],
            '/meta[^>]*http-equiv\s*=\s*["\']refresh["\'][^>]*url\s*=\s*https?:\/\/(?!' . preg_quote(parse_url($this->url, PHP_URL_HOST) ?? '', '/') . ')/i' => ['Meta refresh redirect ke domain lain', 'high'],
        ];

        foreach ($jsRedirectPatterns as $pattern => [$desc, $sev]) {
            try {
                if (preg_match($pattern, $this->html)) {
                    $this->addThreat('hidden_redirect', $desc, 'Redirect otomatis terdeteksi', $sev);
                }
            } catch (\Throwable $e) {
                // Regex error — skip
            }
        }
    }

    private function checkCryptoMiner(): void
    {
        if (empty($this->html)) return;

        $miners = ['coinhive', 'coin-hive', 'cryptonight', 'webmind', 'deepminer', 'crypto-loot', 'miner.js', 'm0rph3us'];

        foreach ($miners as $pattern) {
            if (stripos($this->html, $pattern) !== false) {
                $this->addThreat('crypto_miner', "Skrip crypto miner terdeteksi: {$pattern}", 'Crypto mining script', 'critical');
                break;
            }
        }

        if (preg_match('/\/\*(?:coinhive|miner|cryptonote)\b/i', $this->html)) {
            $this->addThreat('crypto_miner', 'Commented crypto miner script', 'Potential hidden miner', 'high');
        }
    }

    private function checkPhishing(): void
    {
        if (empty($this->html)) return;

        $phishKeywords = ['login', 'password', 'verify', 'account', 'signin', 'banking', 'secure', 'update your'];
        $phishScore = 0;
        $foundKeywords = [];

        foreach ($phishKeywords as $kw) {
            $count = substr_count(strtolower($this->html), $kw);
            if ($count > 3) {
                $phishScore++;
                $foundKeywords[] = $kw . '(' . $count . 'x)';
            }
        }

        $hasForm = preg_match('/<form[^>]*action\s*=\s*["\']https?:\/\/(?!' . preg_quote(parse_url($this->url, PHP_URL_HOST) ?? '', '/') . ')/i', $this->html);
        if ($hasForm) {
            $phishScore += 2;
            $this->addThreat('phishing', 'Form login mengirim data ke domain eksternal', 'Form action mengarah ke server lain', 'critical');
        }

        if ($phishScore >= 3) {
            $this->addThreat('phishing', "Kata kunci phishing terdeteksi: " . implode(', ', $foundKeywords), 'Halaman login mencurigakan', 'high');
        }
    }

    private function checkIframeInjection(): void
    {
        if (empty($this->html)) return;

        $baseHost = parse_url($this->url, PHP_URL_HOST);
        preg_match_all('/<iframe[^>]*src\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $this->html, $matches);

        foreach ($matches[1] as $src) {
            $srcHost = parse_url($src, PHP_URL_HOST);
            if ($srcHost && $srcHost !== $baseHost && !str_contains($src, $baseHost)) {
                if (preg_match('/https?:\/\//i', $src)) {
                    $this->addThreat('iframe_injection', "Iframe ke domain eksternal: {$srcHost}", "Source: {$src}", 'high');
                }
            }
        }
    }

    private function addThreat(string $type, string $description, string $detail, string $severity): void
    {
        $this->threats[] = [
            'type' => $type,
            'description' => $description,
            'detail' => $detail,
            'severity' => $severity,
            'detected_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function calculateScore(): void
    {
        $deductions = ['critical' => 30, 'high' => 15, 'medium' => 7, 'low' => 3];
        foreach ($this->threats as $t) {
            $this->threatScore -= $deductions[$t['severity']] ?? 5;
        }
        $this->threatScore = max(0, $this->threatScore);
    }

    private function getSeverity(): string
    {
        $critical = 0; $high = 0;
        foreach ($this->threats as $t) {
            if ($t['severity'] === 'critical') $critical++;
            if ($t['severity'] === 'high') $high++;
        }
        if ($critical > 0) return 'critical';
        if ($high > 1) return 'high';
        if (!empty($this->threats)) return 'medium';
        return 'clean';
    }
}
