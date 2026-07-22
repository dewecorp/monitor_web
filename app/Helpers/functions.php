<?php
declare(strict_types=1);

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo(string $datetime): string
    {
        $time = strtotime($datetime);
        $diff = time() - $time;
        return match(true) {
            $diff < 60 => 'baru saja',
            $diff < 3600 => round($diff / 60) . ' menit lalu',
            $diff < 86400 => round($diff / 3600) . ' jam lalu',
            $diff < 2592000 => round($diff / 86400) . ' hari lalu',
            default => date('d M Y', $time),
        };
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = min(floor(($bytes ? log($bytes) : 0) / log(1024)), count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('statusBadge')) {
    function statusBadge(bool $isUp): string
    {
        if ($isUp) {
            return '<span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-600 ring-1 ring-emerald-500/20"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Online</span>';
        }
        return '<span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-1 text-[10px] font-semibold text-rose-600 ring-1 ring-rose-500/20"><span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>Offline</span>';
    }
}

if (!function_exists('securityBadge')) {
    function securityBadge(int $score): string
    {
        return match(true) {
            $score >= 80 => '<span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-600 ring-1 ring-emerald-500/20">Aman</span>',
            $score >= 60 => '<span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-600 ring-1 ring-emerald-500/20">Baik</span>',
            $score >= 40 => '<span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-600 ring-1 ring-amber-500/20">Sedang</span>',
            $score >= 20 => '<span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-600 ring-1 ring-amber-500/20">Kurang</span>',
            default => '<span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-1 text-[10px] font-semibold text-rose-600 ring-1 ring-rose-500/20">Rawan</span>',
        };
    }
}

if (!function_exists('csrfField')) {
    function csrfField(): string
    {
        $token = $_SESSION['_csrf_token'] ?? '';
        return '<input type="hidden" name="_csrf_token" value="' . e($token) . '">';
    }
}

if (!function_exists('csrfMeta')) {
    function csrfMeta(): string
    {
        $token = $_SESSION['_csrf_token'] ?? '';
        return '<meta name="csrf-token" content="' . e($token) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('baseUrl')) {
    function baseUrl(): string
    {
        static $base = null;
        if ($base === null) {
            $sn = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
            $base = rtrim(str_replace(['/public/index.php', '/index.php'], '', $sn), '/');
        }
        return $base;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return baseUrl() . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return baseUrl() . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    function route(string $name): string
    {
        $routes = $_SESSION['_routes'] ?? [];
        return $routes[$name] ?? '#';
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = []): string
    {
        $path = VIEW_PATH . '/' . str_replace('.', '/', $name) . '.php';
        if (!file_exists($path)) {
            throw new RuntimeException("View not found: {$name} ({$path})");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): never
    {
        static $basePath = null;
        if ($basePath === null) {
            $sn = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
            $basePath = rtrim(str_replace(['/public/index.php', '/index.php'], '', $sn), '/');
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, $basePath)) {
            $url = $basePath . $url;
        }
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        echo view('errors.' . $code, ['message' => $message]);
        exit;
    }
}
