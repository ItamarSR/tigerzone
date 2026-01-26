<?php

declare(strict_types=1);

namespace TigerZone\Models;

use PDO;
use TigerZone\Core\Database;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(
        string $email,
        string $passwordHash,
        string $name,
        string $inviteCode,
        ?int $referredBy,
        ?string $ip,
        ?string $userAgent,
        ?string $fingerprint
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO users (email, password, name, invite_code, referred_by, ip, user_agent, fingerprint) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $email,
            $passwordHash,
            $name,
            $inviteCode,
            $referredBy,
            $ip,
            $userAgent ? substr($userAgent, 0, 500) : null,
            $fingerprint,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON w.user_id = u.id WHERE u.id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByInviteCode(string $code): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE invite_code = ? LIMIT 1');
        $stmt->execute([strtoupper($code)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateLastLogin(int $userId, string $ip): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_ip = ?, last_login_at = NOW(), updated_at = NOW() WHERE id = ?');
        $stmt->execute([$ip, $userId]);
    }

    public function countByIp(string $ip): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE ip = ?');
        $stmt->execute([$ip]);
        return (int) $stmt->fetchColumn();
    }

    public function countByFingerprint(string $fp): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE fingerprint = ?');
        $stmt->execute([$fp]);
        return (int) $stmt->fetchColumn();
    }

    public function listAll(string $order = 'created_at', string $dir = 'DESC', int $limit = 100, int $offset = 0): array
    {
        $allowed = ['id','email','name','created_at','last_login_at'];
        $order = in_array($order, $allowed) ? $order : 'created_at';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
        $stmt = $this->db->prepare(
            "SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON w.user_id = u.id ORDER BY u.{$order} {$dir} LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function totalCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM users');
        return (int) $stmt->fetchColumn();
    }
}
