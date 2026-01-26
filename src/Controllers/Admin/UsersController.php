<?php

declare(strict_types=1);

namespace TigerZone\Controllers\Admin;

use TigerZone\Models\User;
use TigerZone\Models\Ban;

class UsersController extends BaseAdminController
{
    public function index(): void
    {
        $this->requireAdmin();
        $model = new User();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $users = $model->listAll('created_at', 'DESC', $perPage, $offset);
        $total = $model->totalCount();
        $this->view('admin.users.index', [
            'title' => 'Usuários',
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    public function show(string $id): void
    {
        $this->requireAdmin();
        $model = new User();
        $user = $model->findById((int) $id);
        if (!$user) {
            \flash_set('error', 'Usuário não encontrado.');
            \redirect(\base_url('/admin/usuarios'));
        }
        $banModel = new Ban();
        $isBanned = $banModel->isUserBanned((int) $user['id']);
        $this->view('admin.users.show', [
            'title' => 'Usuário #' . $id,
            'user' => $user,
            'isBanned' => $isBanned,
        ]);
    }

    public function ban(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $userId = (int) $id;
        $reason = \TigerZone\Core\Security::sanitize($_POST['reason'] ?? 'Banimento manual');
        $model = new User();
        $user = $model->findById($userId);
        if (!$user) {
            \flash_set('error', 'Usuário não encontrado.');
            \redirect(\base_url('/admin/usuarios'));
        }
        $banModel = new Ban();
        if ($banModel->isUserBanned($userId)) {
            \flash_set('error', 'Usuário já está banido.');
            \redirect(\base_url('/admin/usuarios/' . $id));
        }
        $adminId = $_SESSION['admin']['id'] ?? null;
        $banModel->add('user', (string) $userId, $reason, $adminId, null);
        \flash_set('success', 'Usuário banido.');
        \redirect(\base_url('/admin/usuarios/' . $id));
    }

    public function unban(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $userId = (int) $id;
        $banModel = new Ban();
        $banModel->removeUserBan($userId);
        \flash_set('success', 'Usuário desbanido.');
        \redirect(\base_url('/admin/usuarios/' . $id));
    }
}
