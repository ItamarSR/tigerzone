<?php

declare(strict_types=1);

namespace TigerZone\Models;

use PDO;
use TigerZone\Core\Database;

class Wallet
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function createForUser(int $userId): void
    {
        $stmt = $this->db->prepare('INSERT INTO wallets (user_id, balance, points) VALUES (?, 0, 0) ON DUPLICATE KEY UPDATE user_id=user_id');
        $stmt->execute([$userId]);
    }

    public function getByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM wallets WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getBalance(int $userId): float
    {
        $w = $this->getByUserId($userId);
        return $w ? (float) $w['balance'] : 0.0;
    }

    public function getPoints(int $userId): int
    {
        $w = $this->getByUserId($userId);
        return $w ? (int) ($w['points'] ?? 0) : 0;
    }

    /** @return array{success: bool, new_balance: float, error?: string} */
    public function add(int $userId, float $amount, string $type, ?string $reference = null, ?array $metadata = null): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'new_balance' => 0.0, 'error' => 'Valor inválido'];
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $w = $stmt->fetch();
            if (!$w) {
                $this->db->rollBack();
                return ['success' => false, 'new_balance' => 0.0, 'error' => 'Carteira não encontrada'];
            }
            $before = (float) $w['balance'];
            $after = $before + $amount;
            $stmt = $this->db->prepare('UPDATE wallets SET balance = ?, updated_at = NOW() WHERE user_id = ?');
            $stmt->execute([$after, $userId]);
            $meta = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;
            $stmt = $this->db->prepare(
                'INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, reference, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $type, $amount, $before, $after, $reference, $meta]);
            $this->db->commit();
            return ['success' => true, 'new_balance' => $after];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'new_balance' => 0.0, 'error' => $e->getMessage()];
        }
    }

    /** @return array{success: bool, new_balance: float, error?: string} */
    public function subtract(int $userId, float $amount, string $type, ?string $reference = null, ?array $metadata = null): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'new_balance' => 0.0, 'error' => 'Valor inválido'];
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $w = $stmt->fetch();
            if (!$w) {
                $this->db->rollBack();
                return ['success' => false, 'new_balance' => 0.0, 'error' => 'Carteira não encontrada'];
            }
            $before = (float) $w['balance'];
            if ($before < $amount) {
                $this->db->rollBack();
                return ['success' => false, 'new_balance' => $before, 'error' => 'Saldo insuficiente'];
            }
            $after = $before - $amount;
            $stmt = $this->db->prepare('UPDATE wallets SET balance = ?, updated_at = NOW() WHERE user_id = ?');
            $stmt->execute([$after, $userId]);
            $meta = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;
            $stmt = $this->db->prepare(
                'INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, reference, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $type, -$amount, $before, $after, $reference, $meta]);
            $this->db->commit();
            return ['success' => true, 'new_balance' => $after];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'new_balance' => 0.0, 'error' => $e->getMessage()];
        }
    }

    public function history(int $userId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public function totalCreditsInCirculation(): float
    {
        $stmt = $this->db->query('SELECT COALESCE(SUM(balance), 0) FROM wallets');
        return (float) $stmt->fetchColumn();
    }

    /** Total real de depósitos (transações type=deposit). */
    public function totalDeposits(): float
    {
        $stmt = $this->db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'deposit'");
        return (float) $stmt->fetchColumn();
    }

    private const POINTS_PER_REAL = 10;

    /**
     * Converte R$ em pontos (1 R$ = 10 pts). Debita balance, credita points.
     * @return array{success: bool, points_added: int, error?: string}
     */
    public function convertToPoints(int $userId, float $amountReal): array
    {
        if ($amountReal <= 0 || $amountReal > 10000) {
            return ['success' => false, 'points_added' => 0, 'error' => 'Valor inválido'];
        }
        $points = (int) round($amountReal * self::POINTS_PER_REAL);
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id, balance, points FROM wallets WHERE user_id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $w = $stmt->fetch();
            if (!$w) {
                $this->db->rollBack();
                return ['success' => false, 'points_added' => 0, 'error' => 'Carteira não encontrada'];
            }
            $balance = (float) $w['balance'];
            if ($balance < $amountReal) {
                $this->db->rollBack();
                return ['success' => false, 'points_added' => 0, 'error' => 'Saldo insuficiente'];
            }
            $newBalance = $balance - $amountReal;
            $newPoints = (int) ($w['points'] ?? 0) + $points;
            $this->db->prepare('UPDATE wallets SET balance = ?, points = ?, updated_at = NOW() WHERE user_id = ?')->execute([$newBalance, $newPoints, $userId]);
            $this->db->commit();
            return ['success' => true, 'points_added' => $points];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'points_added' => 0, 'error' => $e->getMessage()];
        }
    }

    /** @return array{success: bool, new_points: int, error?: string} */
    public function addPoints(int $userId, int $points, string $reference = null): array
    {
        if ($points <= 0) {
            return ['success' => false, 'new_points' => $this->getPoints($userId), 'error' => 'Valor inválido'];
        }
        $stmt = $this->db->prepare('UPDATE wallets SET points = points + ?, updated_at = NOW() WHERE user_id = ?');
        $stmt->execute([$points, $userId]);
        return ['success' => true, 'new_points' => $this->getPoints($userId)];
    }

    /** @return array{success: bool, new_points: int, error?: string} */
    public function subtractPoints(int $userId, int $points, string $reference = null): array
    {
        if ($points <= 0) {
            return ['success' => false, 'new_points' => $this->getPoints($userId), 'error' => 'Valor inválido'];
        }
        $w = $this->getByUserId($userId);
        $current = $w ? (int) ($w['points'] ?? 0) : 0;
        if ($current < $points) {
            return ['success' => false, 'new_points' => $current, 'error' => 'Pontos insuficientes'];
        }
        $stmt = $this->db->prepare('UPDATE wallets SET points = points - ?, updated_at = NOW() WHERE user_id = ?');
        $stmt->execute([$points, $userId]);
        return ['success' => true, 'new_points' => $current - $points];
    }

    public static function pointsPerReal(): int
    {
        return self::POINTS_PER_REAL;
    }
}
