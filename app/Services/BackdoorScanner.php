<?php
declare(strict_types=1);

namespace App\Services;

class BackdoorScanner
{
    private string $path;
    private array $results = [
        'dangerous_functions' => [],
        'obfuscation' => [],
        'suspicious_files' => [],
        'total_files' => 0,
        'scanned_files' => 0,
        'malware_score' => 100,
        'severity' => 'clean',
    ];

    private array $dangerousFunctions = [
        'eval' => ['severity' => 'critical', 'desc' => 'PHP code execution via eval()'],
        'assert' => ['severity' => 'critical', 'desc' => 'PHP code execution via assert()'],
        'system' => ['severity' => 'critical', 'desc' => 'OS command execution via system()'],
        'exec' => ['severity' => 'critical', 'desc' => 'OS command execution via exec()'],
        'shell_exec' => ['severity' => 'critical', 'desc' => 'OS command execution via shell_exec()'],
        'passthru' => ['severity' => 'critical', 'desc' => 'OS command execution via passthru()'],
        'proc_open' => ['severity' => 'critical', 'desc' => 'Process execution via proc_open()'],
        'popen' => ['severity' => 'critical', 'desc' => 'Process execution via popen()'],
        'pcntl_exec' => ['severity' => 'critical', 'desc' => 'Process execution via pcntl_exec()'],
        'create_function' => ['severity' => 'high', 'desc' => 'Deprecated code creation via create_function()'],
        'curl_exec' => ['severity' => 'medium', 'desc' => 'cURL execution — check if used legitimately'],
        'base64_decode' => ['severity' => 'medium', 'desc' => 'Base64 decode — commonly used in obfuscation'],
        'gzinflate' => ['severity' => 'high', 'desc' => 'Gzip inflate — commonly used in obfuscation'],
        'gzdecode' => ['severity' => 'high', 'desc' => 'Gzip decode — commonly used in obfuscation'],
        'str_rot13' => ['severity' => 'low', 'desc' => 'ROT13 encoding — used in obfuscation'],
        'hex2bin' => ['severity' => 'medium', 'desc' => 'Hex decode — commonly used in obfuscation'],
    ];

    private array $obfuscationPatterns = [
        '/base64_decode\s*\(\s*[\'"][A-Za-z0-9+\/=]{100,}[\'"]\s*\)/s' => ['severity' => 'high', 'desc' => 'Long base64 string decoded (potential payload)'],
        '/gzinflate\s*\(\s*base64_decode\s*\(/s' => ['severity' => 'critical', 'desc' => 'Gzip + Base64 nested — common malware pattern'],
        '/\$\w+\s*=\s*[\'"]\s*[A-Za-z0-9+\/=]{200,}\s*[\'"]\s*;/s' => ['severity' => 'medium', 'desc' => 'Very long string literal — potential encoded payload'],
        '/chr\s*\(\s*\d{2,3}\s*\)\s*\.\s*chr\s*\(/s' => ['severity' => 'high', 'desc' => 'chr() concatenation — character-level obfuscation'],
        '/\\\x[0-9a-f]{2}/i' => ['severity' => 'medium', 'desc' => 'Hex escape sequences — possible obfuscation'],
        '/\$\$\w+/s' => ['severity' => 'high', 'desc' => 'Variable variable ($$var) — dynamic code execution'],
        '/preg_replace\s*\(\s*[\'"].*?e[\'"]\s*,\s*[\'"].*?[\'"]\s*,\s*\$/s' => ['severity' => 'critical', 'desc' => 'preg_replace with /e modifier (PHP <7) — code execution'],
        '/file_get_contents\s*\(\s*\$_(GET|POST|REQUEST|SERVER|COOKIE)/s' => ['severity' => 'high', 'desc' => 'Remote file read from user input — LFI/RFI risk'],
        '/include\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/s' => ['severity' => 'critical', 'desc' => 'Dynamic include from user input — LFI/RFI'],
        '/require\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/s' => ['severity' => 'critical', 'desc' => 'Dynamic require from user input — LFI/RFI'],
    ];

    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/\\');
    }

    public function scan(): array
    {
        if (!is_dir($this->path)) {
            $this->results['error'] = 'Directory not found: ' . $this->path;
            return $this->results;
        }

        $this->scanDirectory($this->path);
        $this->calculateScore();
        return $this->results;
    }

    private function scanDirectory(string $dir): void
    {
        $items = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            if ($item->isDir()) {
                $name = $item->getFilename();
                if (in_array($name, ['vendor', 'node_modules', '.git', '.svn', 'cache'])) continue;
                $this->scanDirectory($item->getPathname());
                continue;
            }

            $this->total_files++;
            $ext = strtolower($item->getExtension());

            if (in_array($ext, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7'])) {
                $this->scanned_files++;
                $this->scanFile($item->getPathname());
            } elseif (in_array($ext, ['php', 'phtml', 'phar', 'cgi', 'asp', 'aspx', 'exe'])) {
                if ($this->isInUploadDir($item->getPathname())) {
                    $this->results['suspicious_files'][] = [
                        'file' => $this->getRelativePath($item->getPathname()),
                        'type' => 'executable_in_upload',
                        'severity' => 'critical',
                        'desc' => "Executable file found in upload directory: .{$ext}",
                    ];
                }
            }
        }
    }

    private function scanFile(string $filepath): void
    {
        $content = file_get_contents($filepath);
        if ($content === false) return;

        $relative = $this->getRelativePath($filepath);
        $lines = explode("\n", $content);

        // Check dangerous functions
        foreach ($this->dangerousFunctions as $func => $info) {
            $pattern = '/(?<![a-z_])' . preg_quote($func, '/') . '\s*\(/si';
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $lineNum = $this->findLineNumber($lines, $match[0]);
                    $context = $this->getLineContext($lines, $lineNum);
                    $this->results['dangerous_functions'][] = [
                        'file' => $relative,
                        'function' => $func,
                        'line' => $lineNum,
                        'severity' => $info['severity'],
                        'description' => $info['desc'],
                        'context' => $context,
                    ];
                }
            }
        }

        // Check obfuscation patterns
        foreach ($this->obfuscationPatterns as $pattern => $info) {
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $lineNum = $this->findLineNumber($lines, $match[0]);
                    $context = $this->getLineContext($lines, $lineNum);
                    $this->results['obfuscation'][] = [
                        'file' => $relative,
                        'pattern' => $info['desc'],
                        'line' => $lineNum,
                        'severity' => $info['severity'],
                        'context' => $context,
                    ];
                }
            }
        }

        // Check for webshell indicators
        $this->checkWebshell($content, $relative, $lines);
    }

    private function checkWebshell(string $content, string $relative, array $lines): void
    {
        $indicators = 0;
        $reasons = [];

        // Multiple dangerous functions
        $dangerCount = 0;
        foreach (['eval', 'system', 'exec', 'shell_exec', 'passthru', 'assert'] as $f) {
            if (preg_match('/' . preg_quote($f, '/') . '\s*\(/si', $content)) {
                $dangerCount++;
                $reasons[] = "Uses {$f}()";
            }
        }

        // User input passing to dangerous function
        if (preg_match('/\$_(GET|POST|REQUEST|COOKIE)\s*[^;]*\b(eval|system|exec|shell_exec|assert)\s*\(/si', $content)) {
            $indicators += 3;
            $reasons[] = 'User input passed to dangerous function';
        }

        // File upload + execution pattern
        if (preg_match('/(move_uploaded_file|copy|fwrite|file_put_contents)/si', $content) &&
            preg_match('/\.(php|phtml|phar)\s*[\'"]\s*\)/si', $content)) {
            $indicators += 2;
            $reasons[] = 'File upload with PHP extension — possible shell uploader';
        }

        // Hidden iframe / inject
        if (preg_match('/<iframe[^>]*src\s*=\s*["\']https?:\/\/(?!.*' . preg_quote(parse_url($this->path, PHP_URL_HOST) ?? '', '/') . ')/si', $content)) {
            $indicators += 2;
            $reasons[] = 'Hidden iframe to external domain';
        }

        // SEO spam — hidden links
        if (preg_match('/<div[^>]*style\s*=\s*["\']display\s*:\s*none["\'][^>]*>\s*<a\s+href/i', $content)) {
            $indicators += 2;
            $reasons[] = 'Hidden link (SEO spam)';
        }

        // Base64 payload execution
        if (preg_match('/(eval|assert|system|exec)\s*\(\s*(base64_decode|gzinflate|gzdecode)\s*\(/si', $content)) {
            $indicators += 3;
            $reasons[] = 'Obfuscated payload execution';
        }

        if ($indicators > 0) {
            $severity = $indicators >= 5 ? 'critical' : ($indicators >= 3 ? 'high' : 'medium');
            $this->results['suspicious_files'][] = [
                'file' => $relative,
                'type' => 'webshell',
                'severity' => $severity,
                'desc' => 'Webshell/malware indicators found (' . implode(', ', $reasons) . ')',
                'indicators' => $indicators,
            ];
        }
    }

    private function isInUploadDir(string $path): bool
    {
        $path = str_replace('\\', '/', $path);
        return preg_match('#/(uploads?|storage|tmp|temp|cache|public/assets|images)/#i', $path) === 1;
    }

    private function getRelativePath(string $path): string
    {
        return str_replace(['\\', $this->path . '/', $this->path . '\\'], ['/', '', ''], $path);
    }

    private function findLineNumber(array $lines, string $match): int
    {
        foreach ($lines as $i => $line) {
            if (str_contains($line, substr($match, 0, 30))) {
                return $i + 1;
            }
        }
        return 0;
    }

    private function getLineContext(array $lines, int $lineNum, int $padding = 2): string
    {
        $start = max(0, $lineNum - $padding - 1);
        $end = min(count($lines), $lineNum + $padding);
        $ctx = '';
        for ($i = $start; $i < $end; $i++) {
            $marker = ($i === $lineNum - 1) ? '→ ' : '  ';
            $ctx .= $marker . ($i + 1) . ': ' . $lines[$i] . "\n";
        }
        return $ctx;
    }

    private function calculateScore(): void
    {
        $score = 100;

        $criticalCount = 0;
        $highCount = 0;
        $mediumCount = 0;

        foreach ($this->results['dangerous_functions'] as $f) {
            match($f['severity']) {
                'critical' => $criticalCount++,
                'high' => $highCount++,
                'medium' => $mediumCount++,
                default => null,
            };
        }
        foreach ($this->results['obfuscation'] as $f) {
            match($f['severity']) {
                'critical' => $criticalCount++,
                'high' => $highCount++,
                'medium' => $mediumCount++,
                default => null,
            };
        }
        foreach ($this->results['suspicious_files'] as $f) {
            match($f['severity']) {
                'critical' => $criticalCount++,
                'high' => $highCount++,
                'medium' => $mediumCount++,
                default => null,
            };
        }

        $score -= $criticalCount * 25;
        $score -= $highCount * 10;
        $score -= $mediumCount * 5;

        $this->results['malware_score'] = max(0, $score);
        $this->results['severity'] = match(true) {
            $criticalCount > 0 => 'critical',
            $highCount > 0 => 'high',
            $mediumCount > 0 => 'medium',
            default => 'clean',
        };

        $this->results['summary'] = [
            'critical' => $criticalCount,
            'high' => $highCount,
            'medium' => $mediumCount,
        ];
    }
}
