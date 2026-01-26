<?php

declare(strict_types=1);

namespace TigerZone\Controllers;

use TigerZone\Models\Wallet;
use TigerZone\Models\Ban;
use TigerZone\Game\PrizePool;

class WalletController extends BaseController
{
    private function requireAuth(): void
    {
        if (!auth()) {
            flash_set('error', 'Faça login para acessar a carteira.');
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
        $wallet = new Wallet();
        $balance = $wallet->getBalance((int) $user['id']);
        $this->view('wallet.index', [
            'title' => 'Carteira',
            'balance' => $balance,
            'user' => $user,
        ]);
    }

    public function depositForm(): void
    {
        $this->requireAuth();
        $this->view('wallet.deposit', ['title' => 'Depósito']);
    }

    public function deposit(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $amount = (float) str_replace([',', ' '], ['.', ''], $_POST['amount'] ?? '0');
        if ($amount < 1 || $amount > 10000) {
            flash_set('error', 'Valor deve ser entre R$ 1,00 e R$ 10.000,00.');
            redirect(base_url('/carteira/deposito'));
        }
        $user = auth();
        $wallet = new Wallet();
        $result = $wallet->add((int) $user['id'], $amount, 'deposit', 'SIM-DEP', ['simulated' => true]);
        if (!$result['success']) {
            flash_set('error', $result['error'] ?? 'Erro ao processar depósito.');
            redirect(base_url('/carteira/deposito'));
        }
        $_SESSION['user']['balance'] = $result['new_balance'];
        $pool = new PrizePool();
        $pool->releaseMilestones();
        flash_set('success', 'Depósito de ' . config('app.currency_display') . ' ' . number_format($amount, 2, ',', '.') . ' creditado.');
        redirect(base_url('/carteira'));
    }

    public function withdrawForm(): void
    {
        $this->requireAuth();
        $user = auth();
        $wallet = new Wallet();
        $balance = $wallet->getBalance((int) $user['id']);
        $this->view('wallet.withdraw', ['title' => 'Saque', 'balance' => $balance]);
    }

    public function withdraw(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $amount = (float) str_replace([',', ' '], ['.', ''], $_POST['amount'] ?? '0');
        if ($amount < 1) {
            flash_set('error', 'Valor inválido.');
            redirect(base_url('/carteira/saque'));
        }
        $user = auth();
        $wallet = new Wallet();
        $result = $wallet->subtract((int) $user['id'], $amount, 'withdraw', 'SIM-WD', ['simulated' => true]);
        if (!$result['success']) {
            flash_set('error', $result['error'] ?? 'Erro ao processar saque.');
            redirect(base_url('/carteira/saque'));
        }
        $_SESSION['user']['balance'] = $result['new_balance'];
        flash_set('success', 'Saque de ' . config('app.currency_display') . ' ' . number_format($amount, 2, ',', '.') . ' realizado.');
        redirect(base_url('/carteira'));
    }

    public function history(): void
    {
        $this->requireAuth();
        $user = auth();
        $wallet = new Wallet();
        $transactions = $wallet->history((int) $user['id'], 50, 0);
        $this->view('wallet.history', [
            'title' => 'Histórico',
            'transactions' => $transactions,
        ]);
    }
}
