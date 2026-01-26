<?php

declare(strict_types=1);

namespace TigerZone\Controllers\Admin;

use TigerZone\Models\Game;

class GamesController extends BaseAdminController
{
    public function index(): void
    {
        $this->requireAdmin();
        $model = new Game();
        $games = $model->listActive();
        $this->view('admin.games.index', [
            'title' => 'Jogos',
            'games' => $games,
        ]);
    }
}
