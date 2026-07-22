<?php
declare(strict_types=1);

namespace App\Libraries;

class DotEnv
{
    private string $path;

    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new \RuntimeException(".env file not found: {$path}");
        }
        $this->path = $path;
    }

    public function load(): void
    {
        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}
