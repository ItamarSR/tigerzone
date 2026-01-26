<?php

declare(strict_types=1);

namespace TigerZone\Controllers\Admin;

use TigerZone\Models\Game;

class WinnersController extends BaseAdminController
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        $this->requireAdmin();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;
        $gameModel = new Game();
        $winners = $gameModel->allWinners(self::PER_PAGE, $offset);
        $total = $gameModel->countWinners();
        $totalPages = $total > 0 ? (int) ceil($total / self::PER_PAGE) : 1;
        $this->view('admin.winners.index', [
            'title' => 'Ganhadores',
            'winners' => $winners,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'perPage' => self::PER_PAGE,
        ]);
    }
}
