<?php

declare(strict_types=1);

namespace TigerZone\Models;

use PDO;
use TigerZone\Core\Database;

class Game
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM games WHERE slug = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM games WHERE is_active = 1 ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    public function logPlay(int $userId, int $gameId, float $bet, float $win, float $balanceBefore, float $balanceAfter, ?array $metadata = null): void
    {
        $meta = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;
        $stmt = $this->db->prepare(
            'INSERT INTO game_logs (user_id, game_id, bet, win, balance_before, balance_after, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $gameId, $bet, $win, $balanceBefore, $balanceAfter, $meta]);
    }

    public function historyByUserAndGame(int $userId, int $gameId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM game_logs WHERE user_id = ? AND game_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$userId, $gameId, $limit]);
        return $stmt->fetchAll();
    }

    public function recentWins(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT gl.*, g.name as game_name, u.name as user_name, u.email as user_email FROM game_logs gl 
             JOIN games g ON g.id = gl.game_id 
             JOIN users u ON u.id = gl.user_id 
             WHERE gl.win > 0 ORDER BY gl.created_at DESC LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Todos os ganhadores (win > 0), paginado. Inclui user (name, email), game (name).
     * @return array<int, array>
     */
    public function allWinners(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT gl.id, gl.user_id, gl.game_id, gl.bet, gl.win, gl.balance_before, gl.balance_after, gl.metadata, gl.created_at,
                    g.name as game_name, g.slug as game_slug,
                    u.name as user_name, u.email as user_email
             FROM game_logs gl 
             JOIN games g ON g.id = gl.game_id 
             JOIN users u ON u.id = gl.user_id 
             WHERE gl.win > 0 
             ORDER BY gl.created_at DESC 
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    /** Total de registos de ganhadores (win > 0). */
    public function countWinners(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM game_logs WHERE win > 0');
        return (int) $stmt->fetchColumn();
    }

    /** Soma real de ganhos (game_logs) nas últimas N horas. */
    public function sumRecentWins(int $hours = 24): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(win), 0) FROM game_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)'
        );
        $stmt->execute([$hours]);
        return (float) $stmt->fetchColumn();
    }
}
