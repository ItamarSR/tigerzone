<?php

declare(strict_types=1);

namespace TigerZone\Controllers\Admin;

use TigerZone\Models\Invite;

class InvitesController extends BaseAdminController
{
    public function index(): void
    {
        $this->requireAdmin();
        $model = new Invite();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        $invites = $model->listAll($perPage, $offset);
        $this->view('admin.invites.index', [
            'title' => 'Convites',
            'invites' => $invites,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }
}
