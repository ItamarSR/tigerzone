<?php

declare(strict_types=1);

namespace TigerZone\Controllers\Admin;

use TigerZone\Models\User;
use TigerZone\Models\Wallet;
use TigerZone\Models\AccessLog;
use TigerZone\Models\Settings;

class StatsController extends BaseAdminController
{
    public function index(): void
    {
        $this->requireAdmin();
        $userModel = new User();
        $wallet = new Wallet();
        $accessLog = new AccessLog();
        $settings = new Settings();
        $online = $accessLog->countUniqueIpsLastMinutes(15);
        $this->view('admin.stats.index', [
            'title' => 'Estatísticas',
            'usersTotal' => $userModel->totalCount(),
            'creditsInCirculation' => $wallet->totalCreditsInCirculation(),
            'online' => $online,
            'suspiciousIps' => $accessLog->suspiciousIps(2, 50),
            'settings' => $settings,
        ]);
    }
}
