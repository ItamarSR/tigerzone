<?php

declare(strict_types=1);

namespace TigerZone\Models;

use PDO;
use TigerZone\Core\Database;

class Ban
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function isUserBanned(int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM bans WHERE type = ? AND target = ? AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1'
        );
        $stmt->execute(['user', (string) $userId]);
        return (bool) $stmt->fetch();
    }

    public function isIpBanned(string $ip): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM bans WHERE type = ? AND target = ? AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1'
        );
        $stmt->execute(['ip', $ip]);
        return (bool) $stmt->fetch();
    }

    public function isDeviceBanned(string $fingerprint): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM bans WHERE type = ? AND target = ? AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1'
        );
        $stmt->execute(['device', $fingerprint]);
        return (bool) $stmt->fetch();
    }

    public function isBanned(int $userId, string $ip, string $fingerprint): bool
    {
        return $this->isUserBanned($userId)
            || $this->isIpBanned($ip)
            || $this->isDeviceBanned($fingerprint);
    }

    public function add(string $type, string $target, ?string $reason, ?int $adminId, ?string $expiresAt): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO bans (type, target, reason, admin_id, expires_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$type, $target, $reason, $adminId, $expiresAt ?: null]);
        return (int) $this->db->lastInsertId();
    }

    public function removeUserBan(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM bans WHERE type = ? AND target = ?');
        $stmt->execute(['user', (string) $userId]);
    }

    public function listAll(int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            'SELECT b.*, a.name as admin_name FROM bans b LEFT JOIN admins a ON a.id = b.admin_id ORDER BY b.created_at DESC LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function countBannedUsers(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM bans WHERE type = 'user' AND (expires_at IS NULL OR expires_at > NOW())");
        return (int) $stmt->fetchColumn();
    }
}
