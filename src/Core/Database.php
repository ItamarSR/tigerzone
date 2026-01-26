<?php

declare(strict_types=1);

namespace TigerZone\Core;

use PDO;
use PDOException;

/**
 * Conexão PDO singleton. Prepared statements em todas as operações.
 */
final class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    public static function config(array $config): void
    {
        self::$config = $config;
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $c = self::$config;
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $c['host'] ?? 'localhost',
                $c['port'] ?? 3306,
                $c['database'] ?? 'tigerzone',
                $c['charset'] ?? 'utf8mb4'
            );
            self::$instance = new PDO(
                $dsn,
                $c['username'] ?? 'root',
                $c['password'] ?? '',
                $c['options'] ?? [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }
        return self::$instance;
    }

    public static function disconnect(): void
    {
        self::$instance = null;
    }
}
