<?php

declare(strict_types=1);

namespace TigerZone\Models;

use PDO;
use TigerZone\Core\Database;

class Admin
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUBADMIN = 'subadmin';

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

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array> */
    public function listAll(int $limit = 200): array
    {
        $stmt = $this->db->prepare('SELECT * FROM admins ORDER BY created_at DESC LIMIT ?');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function create(string $email, string $passwordHash, string $name, string $role = self::ROLE_ADMIN): int
    {
        $role = $role === self::ROLE_SUBADMIN ? self::ROLE_SUBADMIN : self::ROLE_ADMIN;
        $stmt = $this->db->prepare('INSERT INTO admins (email, password, name, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$email, $passwordHash, $name, $role]);
        return (int) $this->db->lastInsertId();
    }

    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM admins');
        return (int) $stmt->fetchColumn();
    }
}
