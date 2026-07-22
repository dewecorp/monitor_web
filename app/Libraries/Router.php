<?php
declare(strict_types=1);

namespace App\Libraries;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private array $namedRoutes = [];

    public function get(string $path, array|callable $handler, string $name = ''): self
    {
        return $this->addRoute('GET', $path, $handler, $name);
    }

    public function post(string $path, array|callable $handler, string $name = ''): self
    {
        return $this->addRoute('POST', $path, $handler, $name);
    }

    public function put(string $path, array|callable $handler, string $name = ''): self
    {
        return $this->addRoute('PUT', $path, $handler, $name);
    }

    public function delete(string $path, array|callable $handler, string $name = ''): self
    {
        return $this->addRoute('DELETE', $path, $handler, $name);
    }

    public function addMiddleware(string $path, array $middleware): self
    {
        $this->middleware[$path] = $middleware;
        return $this;
    }

    private function addRoute(string $method, string $path, array|callable $handler, string $name = ''): self
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'original' => $path,
        ];
        if ($name) {
            $this->namedRoutes[$name] = $path;
        }
        return $this;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $basePath = str_replace(['/public/index.php', '/index.php'], '', $scriptName);
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath)) ?: '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            if (is_array($route['handler'])) {
                [$class, $action] = $route['handler'];
                if (!class_exists($class)) {
                    throw new \RuntimeException("Controller not found: {$class}");
                }
                $controller = new $class();
                echo $controller->$action($params);
            } elseif (is_callable($route['handler'])) {
                echo call_user_func($route['handler'], $params);
            }
            return;
        }

        http_response_code(404);
        echo view('errors.404');
    }

    public function getRoutePath(string $name): string
    {
        return $this->namedRoutes[$name] ?? '#';
    }
}
