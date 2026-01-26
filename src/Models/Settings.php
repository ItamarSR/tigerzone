<?php

declare(strict_types=1);

namespace TigerZone\Models;

use PDO;
use TigerZone\Core\Database;

class Settings
{
    private PDO $db;
    private static array $cache = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function get(string $key, ?string $default = null): ?string
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        $stmt = $this->db->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $v = $row ? $row['value'] : $default;
        self::$cache[$key] = $v;
        return $v;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?, updated_at = NOW()');
        $stmt->execute([$key, $value, $value]);
        self::$cache[$key] = $value;
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) ($this->get($key, (string) $default) ?? $default);
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        $v = $this->get($key, null);
        return $v !== null ? (float) $v : $default;
    }
}
