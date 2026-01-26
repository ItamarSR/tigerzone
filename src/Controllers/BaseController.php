<?php

declare(strict_types=1);

namespace TigerZone\Controllers;

use TigerZone\Core\Database;
use TigerZone\Core\Security;

abstract class BaseController
{
    protected function view(string $name, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $name) . '.php';
        if (!is_file($viewPath)) {
            http_response_code(500);
            echo 'View not found: ' . $name;
            return;
        }
        require $viewPath;
    }

    protected function json(mixed $data, int $code = 200): never
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function validateCsrf(): bool
    {
        $token = $_POST[config('app.csrf_token_name', '_token')] ?? null;
        if (!Security::validateCsrf($token)) {
            if ($this->wantsJson()) {
                $this->json(['error' => 'Token CSRF inválido'], 403);
            }
            flash_set('error', 'Sessão expirada. Tente novamente.');
            redirect(base_url('/'));
        }
        return true;
    }

    protected function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }
}
