<?php

declare(strict_types=1);

namespace TigerZone\Controllers;

use TigerZone\Models\Invite;
use TigerZone\Models\Ban;

class InviteController extends BaseController
{
    private function requireAuth(): void
    {
        if (!auth()) {
            flash_set('error', 'Faça login para acessar.');
            redirect(base_url('/login'));
        }
        $ban = new Ban();
        $u = auth();
        if ($ban->isBanned((int) $u['id'], \client_ip(), \TigerZone\Core\Security::fingerprint())) {
            unset($_SESSION['user']);
            flash_set('error', 'Acesso bloqueado.');
            redirect(base_url('/login'));
        }
    }

    public function index(): void
    {
        $this->requireAuth();
        $user = auth();
        $inviteModel = new Invite();
        $list = $inviteModel->listByUser((int) $user['id']);
        $this->view('invite.index', [
            'title' => 'Convites',
            'inviteCode' => $user['invite_code'],
            'invites' => $list,
        ]);
    }

    public function generate(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->json(['code' => auth()['invite_code'] ?? '']);
    }

    /**
     * Landing pública para link de convite (ex: /convite/ABC123).
     * Redireciona para registro com ref=code.
     */
    public function landing(string $code): void
    {
        $code = strtoupper(\TigerZone\Core\Security::sanitize($code));
        if ($code === '') {
            redirect(base_url('/registro'));
        }
        redirect(base_url('/registro?ref=' . urlencode($code)));
    }
}
