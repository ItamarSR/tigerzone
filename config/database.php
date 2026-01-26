<?php

declare(strict_types=1);

/**
 * TigerZone – Configuração do Banco de Dados
 * MySQL + PDO
 */

return [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'tigerzon_chopp',
    'username' => 'tigerzon_master',
    'password' => 'j[Rvm*xI963M0D',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
