<?php

declare(strict_types=1);

namespace TigerZone\Models;

use PDO;
use TigerZone\Core\Database;

class AccessLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function log(?int $userId, string $ip, ?string $userAgent, ?string $fingerprint, ?string $action, ?string $path): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO access_logs (user_id, ip, user_agent, fingerprint, action, path) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $ip,
            $userAgent ? substr($userAgent, 0, 500) : null,
            $fingerprint,
            $action ? substr($action, 0, 64) : null,
            $path ? substr($path, 0, 500) : null,
        ]);
    }

    public function countUniqueIpsLastMinutes(int $minutes = 15): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(DISTINCT ip) FROM access_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)'
        );
        $stmt->execute([$minutes]);
        return (int) $stmt->fetchColumn();
    }

    public function suspiciousIps(int $threshold = 3, int $limit = 50): array
    {
        $stmt = $this->db->query(
            'SELECT ip, COUNT(DISTINCT user_id) as accounts, MAX(created_at) as last_seen 
             FROM access_logs WHERE user_id IS NOT NULL 
             GROUP BY ip HAVING accounts >= ' . (int) $threshold . ' 
             ORDER BY accounts DESC LIMIT ' . (int) $limit
        );
        return $stmt->fetchAll();
    }
}
