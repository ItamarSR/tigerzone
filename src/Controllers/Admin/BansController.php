<?php

declare(strict_types=1);

namespace TigerZone\Controllers\Admin;

use TigerZone\Models\Ban;

class BansController extends BaseAdminController
{
    public function index(): void
    {
        $this->requireAdmin();
        $model = new Ban();
        $bans = $model->listAll(200);
        $this->view('admin.bans.index', [
            'title' => 'Banimentos',
            'bans' => $bans,
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $type = \TigerZone\Core\Security::sanitize($_POST['type'] ?? '');
        $target = \TigerZone\Core\Security::sanitize($_POST['target'] ?? '');
        $reason = \TigerZone\Core\Security::sanitize($_POST['reason'] ?? '');
        if (!in_array($type, ['user', 'ip', 'device'], true) || $target === '') {
            \flash_set('error', 'Tipo e alvo são obrigatórios.');
            \redirect(\base_url('/admin/banimentos'));
        }
        $adminId = $_SESSION['admin']['id'] ?? null;
        $model = new Ban();
        $model->add($type, $target, $reason ?: null, $adminId, null);
        \flash_set('success', 'Banimento registrado.');
        \redirect(\base_url('/admin/banimentos'));
    }
}
