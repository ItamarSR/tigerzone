<?php

declare(strict_types=1);

namespace TigerZone\Controllers\Admin;

use TigerZone\Models\User;
use TigerZone\Models\Ban;
use TigerZone\Models\Wallet;
use TigerZone\Models\AccessLog;

class DashboardController extends BaseAdminController
{
    public function index(): void
    {
        $this->requireAdmin();
        $userModel = new User();
        $banModel = new Ban();
        $wallet = new Wallet();
        $accessLog = new AccessLog();
        $this->view('admin.dashboard.index', [
            'title' => 'Dashboard',
            'usersTotal' => $userModel->totalCount(),
            'bannedTotal' => $banModel->countBannedUsers(),
            'creditsInCirculation' => $wallet->totalCreditsInCirculation(),
            'suspiciousIps' => $accessLog->suspiciousIps(3, 20),
        ]);
    }
}
