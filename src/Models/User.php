<?php

declare(strict_types=1);

namespace TigerZone\Models;

use PDO;
use PDOException;
use TigerZone\Core\Database;

class User
{
    private PDO $db;
    private ?bool $phoneColumnsReady = null;
    private ?bool $emailColumnsReady = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Detecta se o banco já tem as colunas de celular/verificação.
     * Evita erro fatal quando a migration ainda não foi aplicada.
     */
    public function supportsPhone(): bool
    {
        if ($this->phoneColumnsReady !== null) {
            return $this->phoneColumnsReady;
        }
        try {
            $dbName = (string) $this->db->query('SELECT DATABASE()')->fetchColumn();
            if ($dbName === '') {
                return $this->phoneColumnsReady = false;
            }
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'phone'"
            );
            $stmt->execute([$dbName]);
            return $this->phoneColumnsReady = ((int) $stmt->fetchColumn() > 0);
        } catch (PDOException $e) {
            return $this->phoneColumnsReady = false;
        }
    }

    public function supportsEmailVerification(): bool
    {
        if ($this->emailColumnsReady !== null) {
            return $this->emailColumnsReady;
        }
        try {
            $dbName = (string) $this->db->query('SELECT DATABASE()')->fetchColumn();
            if ($dbName === '') {
                return $this->emailColumnsReady = false;
            }
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verification_token'"
            );
            $stmt->execute([$dbName]);
            return $this->emailColumnsReady = ((int) $stmt->fetchColumn() > 0);
        } catch (PDOException $e) {
            return $this->emailColumnsReady = false;
        }
    }

    public function create(
        string $email,
        string $passwordHash,
        string $name,
        string $phone,
        string $inviteCode,
        ?int $referredBy,
        ?string $ip,
        ?string $userAgent,
        ?string $fingerprint
    ): int {
        if ($this->supportsPhone()) {
            $stmt = $this->db->prepare(
                'INSERT INTO users (email, password, name, phone, invite_code, referred_by, ip, user_agent, fingerprint) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $email,
                $passwordHash,
                $name,
                $phone,
                $inviteCode,
                $referredBy,
                $ip,
                $userAgent ? substr($userAgent, 0, 500) : null,
                $fingerprint,
            ]);
        } else {
            // Fallback para bancos antigos (sem coluna phone)
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
        }
        return (int) $this->db->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByPhone(string $phone): ?array
    {
        if (!$this->supportsPhone()) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
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

    public function setPhoneVerification(int $userId, string $code, int $ttlMinutes): void
    {
        if (!$this->supportsPhone()) {
            return;
        }
        $stmt = $this->db->prepare(
            'UPDATE users 
             SET phone_verification_code = ?, 
                 phone_verification_expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                 updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$code, $ttlMinutes, $userId]);
    }

    public function verifyPhone(int $userId): void
    {
        if (!$this->supportsPhone()) {
            return;
        }
        $stmt = $this->db->prepare(
            'UPDATE users 
             SET phone_verified_at = NOW(),
                 phone_verification_code = NULL,
                 phone_verification_expires_at = NULL,
                 updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$userId]);
    }

    public function isPhoneVerified(array $user): bool
    {
        return !empty($user['phone_verified_at']);
    }

    public function findByEmailVerificationToken(string $token): ?array
    {
        if (!$this->supportsEmailVerification()) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email_verification_token = ? LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setEmailVerification(int $userId, string $token, int $ttlMinutes): void
    {
        if (!$this->supportsEmailVerification()) {
            return;
        }
        $stmt = $this->db->prepare(
            'UPDATE users
             SET email_verified_at = NULL,
                 email_verification_token = ?,
                 email_verification_expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                 updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$token, $ttlMinutes, $userId]);
    }

    public function verifyEmail(int $userId): void
    {
        if (!$this->supportsEmailVerification()) {
            return;
        }
        $stmt = $this->db->prepare(
            'UPDATE users
             SET email_verified_at = NOW(),
                 email_verification_token = NULL,
                 email_verification_expires_at = NULL,
                 updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$userId]);
    }

    public function isEmailVerified(array $user): bool
    {
        return !empty($user['email_verified_at']);
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
