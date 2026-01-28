<?php

declare(strict_types=1);

/**
 * TigerZone – Configuração da Aplicação
 */

return [
    'name' => 'TigerZone',
    'env' => 'production',
    'debug' => false,
    'url' => 'http://localhost',
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'pt_BR',
    'currency_display' => 'R$',
    'credit_name' => 'créditos',
    'session_lifetime' => 7200,
    'csrf_token_name' => '_token',
    'password_algo' => defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT,
    'password_options' => defined('PASSWORD_ARGON2ID') ? ['memory_cost' => 65536, 'time_cost' => 4] : ['cost' => 12],
    'games' => [
        'fortune_tiger' => ['name' => 'Fortune Tiger', 'slug' => 'fortune-tiger', 'icon' => 'tiger'],
        'fortune_dragon' => ['name' => 'Fortune Dragon', 'slug' => 'fortune-dragon', 'icon' => 'dragon'],
        'fortune_ox' => ['name' => 'Fortune Ox', 'slug' => 'fortune-ox', 'icon' => 'ox'],
    ],
    // Celular
    'phone' => [
        'default_country_code' => '55',
    ],

    // Confirmação por e-mail
    'email_verification' => [
        'enabled' => true,
        'token_ttl_minutes' => 60,
        // Para testes (quando o servidor não envia e-mail), pode exibir o token no flash.
        'show_token_in_flash' => env('EMAIL_SHOW_TOKEN_IN_FLASH', false),
    ],

    // Envio de e-mail (usa mail() do PHP).
    'mail' => [
        'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@localhost'),
        'from_name' => env('MAIL_FROM_NAME', 'TigerZone'),
    ],
];
