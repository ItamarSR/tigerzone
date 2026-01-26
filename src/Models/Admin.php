<?php

declare(strict_types=1);

namespace TigerZone\Models;

use PDO;
use TigerZone\Core\Database;

class Admin
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $email, string $passwordHash, string $name): int
    {
        $stmt = $this->db->prepare('INSERT INTO admins (email, password, name) VALUES (?, ?, ?)');
        $stmt->execute([$email, $passwordHash, $name]);
        return (int) $this->db->lastInsertId();
    }

    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM admins');
        return (int) $stmt->fetchColumn();
    }
}
