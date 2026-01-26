<?php

declare(strict_types=1);

namespace TigerZone\Models;

use PDO;
use TigerZone\Core\Database;

class Invite
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function registerUse(
        int $referrerId,
        string $code,
        int $invitedUserId,
        string $inviterIp,
        string $invitedIp,
        ?string $userAgent,
        bool $bonusGiven,
        ?string $bonusBlockedReason
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO invites (user_id, code, invited_user_id, inviter_ip, invited_ip, user_agent, bonus_given, bonus_blocked_reason) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $referrerId,
            $code,
            $invitedUserId,
            $inviterIp,
            $invitedIp,
            $userAgent ? substr($userAgent, 0, 500) : null,
            $bonusGiven ? 1 : 0,
            $bonusBlockedReason,
        ]);
    }

    public function countByInviterIp(string $ip): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM invites WHERE inviter_ip = ?');
        $stmt->execute([$ip]);
        return (int) $stmt->fetchColumn();
    }

    public function countByInvitedIp(string $ip): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM invites WHERE invited_ip = ?');
        $stmt->execute([$ip]);
        return (int) $stmt->fetchColumn();
    }

    /** Convites do mesmo IP (referidor = convidado) não geram bônus */
    public function sameIpUsedForInviterAndInvited(string $inviterIp, string $invitedIp): bool
    {
        return $inviterIp === $invitedIp;
    }

    public function listByUser(int $userId, int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, u.name as invited_name, u.email as invited_email, u.created_at as invited_at 
             FROM invites i LEFT JOIN users u ON u.id = i.invited_user_id 
             WHERE i.user_id = ? ORDER BY i.created_at DESC LIMIT ?'
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function listAll(int $limit = 200, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, u.name as referrer_name, u.email as referrer_email, 
                    u2.name as invited_name, u2.email as invited_email 
             FROM invites i 
             JOIN users u ON u.id = i.user_id 
             LEFT JOIN users u2 ON u2.id = i.invited_user_id 
             ORDER BY i.created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
}
