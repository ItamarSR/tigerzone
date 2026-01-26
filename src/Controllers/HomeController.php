<?php

declare(strict_types=1);

namespace TigerZone\Controllers;

use TigerZone\Models\Game;
use TigerZone\Models\Settings;

class HomeController extends BaseController
{
    public function index(): void
    {
        $gameModel = new Game();
        $games = $gameModel->listActive();
        $settings = new Settings();
        $this->view('home.index', [
            'title' => 'Início',
            'games' => $games,
            'settings' => $settings,
        ]);
    }
}
