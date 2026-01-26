<?php

declare(strict_types=1);

namespace TigerZone\Core;

/**
 * CSRF, sanitização, hashing e helpers de segurança.
 */
final class Security
{
    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function csrfField(string $name = '_token'): string
    {
        return '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . self::csrfToken() . '">';
    }

    public static function validateCsrf(?string $token, string $name = '_token'): bool
    {
        return $token !== null && hash_equals($_SESSION['_csrf_token'] ?? '', $token);
    }

    public static function sanitize(string $value, int $filter = FILTER_SANITIZE_SPECIAL_CHARS): string
    {
        return trim((string) filter_var($value, $filter));
    }

    public static function sanitizeEmail(string $value): string
    {
        return filter_var(trim($value), FILTER_SANITIZE_EMAIL) ?: '';
    }

    public static function hashPassword(string $password): string
    {
        $opts = config('app.password_options', []);
        return password_hash($password, config('app.password_algo', PASSWORD_DEFAULT), $opts);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function generateInviteCode(): string
    {
        return strtoupper(bin2hex(random_bytes(4)));
    }

    public static function fingerprint(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        return hash('sha256', $ua . '|' . $lang);
    }
}
