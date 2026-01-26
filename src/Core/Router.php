<?php

declare(strict_types=1);

namespace TigerZone\Core;

/**
 * Router simples: GET/POST -> Controller@action
 */
class Router
{
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, string $handler): self
    {
        $this->routes['GET'][$path] = $handler;
        return $this;
    }

    public function post(string $path, string $handler): self
    {
        $this->routes['POST'][$path] = $handler;
        return $this;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = $this->normalizeUri($uri);
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route => $handler) {
            $params = $this->match($route, $uri);
            if ($params !== null) {
                [$controller, $action] = explode('@', $handler);
                $controller = "TigerZone\\Controllers\\{$controller}";
                if (!class_exists($controller)) {
                    http_response_code(500);
                    echo 'Controller not found.';
                    return;
                }
                $ctrl = new $controller();
                if (!method_exists($ctrl, $action)) {
                    http_response_code(500);
                    echo 'Action not found.';
                    return;
                }
                $ctrl->$action(...$params);
                return;
            }
        }

        http_response_code(404);
        (new \TigerZone\Controllers\ErrorController())->notFound();
    }

    private function normalizeUri(string $uri): string
    {
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $uri = '/' . trim($uri, '/');
        return $uri === '' ? '/' : $uri;
    }

    private function match(string $route, string $uri): ?array
    {
        $route = '/' . trim($route, '/');
        if ($route !== '/' && $route === $uri) {
            return [];
        }
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $route);
        $pattern = '#^' . $pattern . '$#';
        if (preg_match($pattern, $uri, $m)) {
            array_shift($m);
            return $m;
        }
        return null;
    }
}
