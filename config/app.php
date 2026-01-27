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
    // SMS (confirmação de celular). Por padrão é simulado (não envia SMS real).
    'sms' => [
        'enabled' => true,
        'driver' => env('SMS_DRIVER', 'twilio'), // simulated | twilio | ...
        'code_ttl_minutes' => 10,
        // Em modo simulado, exibe o código no flash (para testes).
        'show_code_in_flash' => env('SMS_SHOW_CODE_IN_FLASH', false),
        // País default para normalização (Brasil).
        'default_country_code' => '55',
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID', ''),
            'auth_token' => env('TWILIO_AUTH_TOKEN', ''),
            'from' => env('TWILIO_FROM', ''), // Ex.: +14155552671
        ],
    ],
];
