<?php

declare(strict_types=1);

namespace TigerZone\Controllers;

use TigerZone\Models\AccessLog;
use TigerZone\Models\Game;
use TigerZone\Models\Wallet;
use TigerZone\Core\Security;

class StatsController extends BaseController
{
    /**
     * Estatísticas reais (online, ganhos recentes, depósitos) para a barra da home.
     */
    public function index(): void
    {
        $log = new AccessLog();
        $log->log(\auth_id(), \client_ip(), \user_agent(), Security::fingerprint(), 'stats', $_SERVER['REQUEST_URI'] ?? null);

        $accessLog = new AccessLog();
        $online = max(0, $accessLog->countUniqueIpsLastMinutes(15));

        $gameModel = new Game();
        $recentWins = $gameModel->sumRecentWins(24);

        $wallet = new Wallet();
        $totalDeposits = $wallet->totalDeposits();

        $this->json([
            'online' => $online,
            'recent_wins' => config('app.currency_display') . ' ' . number_format($recentWins, 2, ',', '.'),
            'total_deposits' => config('app.currency_display') . ' ' . number_format($totalDeposits, 2, ',', '.'),
        ]);
    }
}
