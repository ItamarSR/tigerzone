<?php

declare(strict_types=1);

/**
 * Helpers globais
 */

function config(string $key, mixed $default = null): mixed
{
    static $app = null;
    static $db = null;
    if ($app === null) {
        $app = require __DIR__ . '/../../config/app.php';
    }
    if (str_starts_with($key, 'app.')) {
        $k = substr($key, 4);
        $v = $app;
        foreach (explode('.', $k) as $part) {
            $v = $v[$part] ?? $default;
        }
        return $v;
    }
    if (str_starts_with($key, 'database.')) {
        if ($db === null) {
            $db = require __DIR__ . '/../../config/database.php';
        }
        $k = substr($key, 9);
        return $db[$k] ?? $default;
    }
    return $default;
}

function env(string $key, mixed $default = null): mixed
{
    $v = $_ENV[$key] ?? getenv($key);
    return $v !== false && $v !== '' ? $v : $default;
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) (env('APP_URL') ?: config('app.url')), '/');
    if ($base === '' || $base === 'http://localhost') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = $scheme . '://' . $host;
    }
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

/**
 * URL dos assets (CSS, JS). Usa caminho relativo à raiz para carregar
 * sempre no mesmo domínio da página. Use APP_BASE_PATH se a app estiver em subdir.
 */
function asset(string $path): string
{
    $base = rtrim((string) env('APP_BASE_PATH', ''), '/');
    return $base . '/assets/' . ltrim($path, '/');
}

function redirect(string $url, int $code = 302): never
{
    header('Location: ' . $url, true, $code);
    exit;
}

function old(string $key, string $default = ''): string
{
    return \TigerZone\Core\Security::sanitize($_SESSION['_old'][$key] ?? $default);
}

function flash(string $key): ?string
{
    $v = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $v;
}

function flash_set(string $key, string $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function auth(): ?array
{
    return $_SESSION['user'] ?? null;
}

function auth_id(): ?int
{
    $u = auth();
    return $u ? (int) $u['id'] : null;
}

function is_admin(): bool
{
    return !empty($_SESSION['admin']);
}

function client_ip(): string
{
    return $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['HTTP_X_REAL_IP']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';
}

function user_agent(): string
{
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}
