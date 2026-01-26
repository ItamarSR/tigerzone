<?php

declare(strict_types=1);

namespace TigerZone\Controllers;

use TigerZone\Core\Security;
use TigerZone\Models\User;
use TigerZone\Models\Wallet;
use TigerZone\Models\Ban;
use TigerZone\Models\Invite;

class AuthController extends BaseController
{
    public function loginForm(): void
    {
        if (auth()) {
            redirect(base_url('/'));
        }
        $this->view('auth.login', ['title' => 'Entrar']);
    }

    public function login(): void
    {
        $this->validateCsrf();
        $email = Security::sanitizeEmail($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$email || !$password) {
            flash_set('error', 'E-mail e senha são obrigatórios.');
            redirect(base_url('/login'));
        }
        $userModel = new User();
        $user = $userModel->findByEmail($email);
        if (!$user || !Security::verifyPassword($password, $user['password'])) {
            flash_set('error', 'E-mail ou senha incorretos.');
            redirect(base_url('/login'));
        }
        $ban = new Ban();
        $ip = \client_ip();
        $fp = Security::fingerprint();
        if ($ban->isBanned((int) $user['id'], $ip, $fp)) {
            flash_set('error', 'Acesso bloqueado. Entre em contato com o suporte.');
            redirect(base_url('/login'));
        }
        $userModel->updateLastLogin((int) $user['id'], $ip);
        $user = $userModel->findById((int) $user['id']);
        $_SESSION['user'] = $user;
        flash_set('success', 'Bem-vindo de volta!');
        redirect(base_url('/'));
    }

    public function registerForm(): void
    {
        if (auth()) {
            redirect(base_url('/'));
        }
        $code = Security::sanitize($_GET['ref'] ?? '');
        $this->view('auth.register', ['title' => 'Cadastro', 'ref' => $code]);
    }

    public function register(): void
    {
        $this->validateCsrf();
        $email = Security::sanitizeEmail($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = Security::sanitize($_POST['name'] ?? '');
        $ref = strtoupper(Security::sanitize($_POST['ref'] ?? ''));
        if (!$email || !$password || !$name) {
            flash_set('error', 'Preencha todos os campos obrigatórios.');
            $_SESSION['_old'] = ['email' => $email, 'name' => $name, 'ref' => $ref];
            redirect(base_url('/registro'));
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'E-mail inválido.');
            $_SESSION['_old'] = ['email' => $email, 'name' => $name, 'ref' => $ref];
            redirect(base_url('/registro'));
        }
        if (strlen($password) < 6) {
            flash_set('error', 'A senha deve ter no mínimo 6 caracteres.');
            $_SESSION['_old'] = ['email' => $email, 'name' => $name, 'ref' => $ref];
            redirect(base_url('/registro'));
        }
        $ip = \client_ip();
        $ua = \user_agent();
        $fp = Security::fingerprint();
        $ban = new Ban();
        if ($ban->isIpBanned($ip) || $ban->isDeviceBanned($fp)) {
            flash_set('error', 'Não foi possível concluir o cadastro.');
            redirect(base_url('/registro'));
        }
        $userModel = new User();
        if ($userModel->findByEmail($email)) {
            flash_set('error', 'Este e-mail já está cadastrado.');
            $_SESSION['_old'] = ['email' => $email, 'name' => $name, 'ref' => $ref];
            redirect(base_url('/registro'));
        }
        $inviteCode = Security::generateInviteCode();
        $referredBy = null;
        $referrer = null;
        if ($ref) {
            $referrer = $userModel->findByInviteCode($ref);
            if ($referrer) {
                $referredBy = (int) $referrer['id'];
            }
        }
        $userId = $userModel->create(
            $email,
            Security::hashPassword($password),
            $name,
            $inviteCode,
            $referredBy,
            $ip,
            $ua,
            $fp
        );
        $wallet = new Wallet();
        $wallet->createForUser($userId);
        $wallet->add($userId, 25.00, 'bonus', 'BONUS_CADASTRO', ['source' => 'signup']);
        $user = $userModel->findById($userId);

        $bonusGiven = false;
        $bonusBlocked = null;
        if ($referredBy !== null && $referrer) {
            $inviteModel = new Invite();
            $sameIp = $inviteModel->sameIpUsedForInviterAndInvited($referrer['ip'] ?? '', $ip);
            $multipleAccounts = $userModel->countByIp($ip) > 1;
            if ($sameIp || $multipleAccounts) {
                $bonusBlocked = $sameIp ? 'Mesmo IP' : 'Múltiplas contas no IP';
            } else {
                $bonusGiven = true;
                $wallet->add($userId, 10.00, 'bonus', 'REF:' . $ref, ['referrer_id' => $referredBy]);
                $user = $userModel->findById($userId);
            }
            $inviteModel->registerUse(
                $referredBy,
                $ref,
                $userId,
                $referrer['ip'] ?? '',
                $ip,
                $ua,
                $bonusGiven,
                $bonusBlocked
            );
        }

        $_SESSION['user'] = $user;
        flash_set('success', 'Conta criada com sucesso! Bónus de ' . config('app.currency_display') . ' ' . number_format(25.00, 2, ',', '.') . ' creditado.');
        redirect(base_url('/'));
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        flash_set('success', 'Você saiu.');
        redirect(base_url('/'));
    }
}
