<?php

declare(strict_types=1);

namespace TigerZone\Controllers;

use TigerZone\Core\Security;
use TigerZone\Models\User;
use TigerZone\Models\Wallet;
use TigerZone\Models\Ban;
use TigerZone\Models\Invite;
use TigerZone\Sms\SmsService;

class AuthController extends BaseController
{
    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        // remove zeros à esquerda comuns em discagem
        $digits = ltrim($digits, '0');
        return $digits;
    }

    private function toE164(string $digits): string
    {
        $cc = (string) config('app.sms.default_country_code', '55');
        // Se já tem DDI (ex.: 55...), usa como está; caso contrário assume BR
        if (strlen($digits) >= 12 && str_starts_with($digits, $cc)) {
            return '+' . $digits;
        }
        if (strlen($digits) >= 10 && strlen($digits) <= 11) {
            return '+' . $cc . $digits;
        }
        return '+' . $digits;
    }

    private function maskPhone(string $digits): string
    {
        $len = strlen($digits);
        if ($len <= 4) return $digits;
        $tail = substr($digits, -4);
        return str_repeat('•', max(0, $len - 4)) . $tail;
    }

    private function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }

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

        // Se SMS estiver habilitado, exige celular confirmado
        if (config('app.sms.enabled', false) && empty($user['phone_verified_at'])) {
            $_SESSION['pending_phone_user_id'] = (int) $user['id'];
            $_SESSION['pending_phone_masked'] = $this->maskPhone((string) ($user['phone'] ?? ''));
            flash_set('error', 'Confirme seu celular para entrar.');
            redirect(base_url('/confirmar-celular'));
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
        $phoneRaw = Security::sanitize($_POST['phone'] ?? '');
        $phone = $this->normalizePhone($phoneRaw);
        $ref = strtoupper(Security::sanitize($_POST['ref'] ?? ''));
        if (!$email || !$password || !$name || !$phone) {
            flash_set('error', 'Preencha todos os campos obrigatórios.');
            $_SESSION['_old'] = ['email' => $email, 'name' => $name, 'phone' => $phoneRaw, 'ref' => $ref];
            redirect(base_url('/registro'));
        }
        if (strlen($phone) < 10 || strlen($phone) > 13) {
            flash_set('error', 'Celular inválido.');
            $_SESSION['_old'] = ['email' => $email, 'name' => $name, 'phone' => $phoneRaw, 'ref' => $ref];
            redirect(base_url('/registro'));
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'E-mail inválido.');
            $_SESSION['_old'] = ['email' => $email, 'name' => $name, 'phone' => $phoneRaw, 'ref' => $ref];
            redirect(base_url('/registro'));
        }
        if (strlen($password) < 6) {
            flash_set('error', 'A senha deve ter no mínimo 6 caracteres.');
            $_SESSION['_old'] = ['email' => $email, 'name' => $name, 'phone' => $phoneRaw, 'ref' => $ref];
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
        if (!$userModel->supportsPhone()) {
            flash_set('error', 'Banco desatualizado: aplique a migration `database/migrations/005_phone_verification.sql` (coluna phone) e tente novamente.');
            $_SESSION['_old'] = ['email' => $email, 'name' => $name, 'phone' => $phoneRaw, 'ref' => $ref];
            redirect(base_url('/registro'));
        }
        if ($userModel->findByEmail($email)) {
            flash_set('error', 'Este e-mail já está cadastrado.');
            $_SESSION['_old'] = ['email' => $email, 'name' => $name, 'phone' => $phoneRaw, 'ref' => $ref];
            redirect(base_url('/registro'));
        }
        if ($userModel->findByPhone($phone)) {
            flash_set('error', 'Este celular já está cadastrado.');
            $_SESSION['_old'] = ['email' => $email, 'name' => $name, 'phone' => $phoneRaw, 'ref' => $ref];
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
            $phone,
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

        // Confirmação de celular (opcional)
        if (config('app.sms.enabled', false)) {
            $code = $this->generateCode();
            $ttl = (int) config('app.sms.code_ttl_minutes', 10);
            $userModel->setPhoneVerification($userId, $code, $ttl);
            $to = $this->toE164($phone);
            $sms = new SmsService();
            $send = $sms->sendVerificationCode($to, $code);
            $_SESSION['pending_phone_user_id'] = $userId;
            $_SESSION['pending_phone_masked'] = $this->maskPhone($phone);
            $msg = 'Conta criada! Enviamos um código para confirmar seu celular.';
            if (!(bool) $send['success']) {
                $detail = (string) ($send['error'] ?? '');
                $msg = 'Conta criada! Não foi possível enviar o SMS agora. Tente reenviar o código.'
                    . ($detail ? ' Motivo: ' . $detail : '');
            } elseif (config('app.sms.driver', 'simulated') === 'simulated' && config('app.sms.show_code_in_flash', false)) {
                $msg .= ' (SIMULADO: código ' . $code . ')';
            }
            flash_set('success', $msg);
            redirect(base_url('/confirmar-celular'));
        }

        $_SESSION['user'] = $user;
        flash_set('success', 'Conta criada com sucesso! Bónus de ' . config('app.currency_display') . ' ' . number_format(25.00, 2, ',', '.') . ' creditado.');
        redirect(base_url('/'));
    }

    public function confirmPhoneForm(): void
    {
        if (auth()) {
            redirect(base_url('/'));
        }
        $uid = (int) ($_SESSION['pending_phone_user_id'] ?? 0);
        if ($uid <= 0) {
            redirect(base_url('/login'));
        }
        $masked = (string) ($_SESSION['pending_phone_masked'] ?? 'seu celular');
        $this->view('auth.confirm-phone', ['title' => 'Confirmar celular', 'masked_phone' => $masked]);
    }

    public function confirmPhone(): void
    {
        $this->validateCsrf();
        $uid = (int) ($_SESSION['pending_phone_user_id'] ?? 0);
        if ($uid <= 0) {
            redirect(base_url('/login'));
        }
        $code = preg_replace('/\D+/', '', (string) ($_POST['code'] ?? '')) ?? '';
        if (strlen($code) < 4 || strlen($code) > 8) {
            flash_set('error', 'Código inválido.');
            redirect(base_url('/confirmar-celular'));
        }

        $userModel = new User();
        $u = $userModel->findById($uid);
        if (!$u) {
            unset($_SESSION['pending_phone_user_id'], $_SESSION['pending_phone_masked']);
            flash_set('error', 'Sessão expirada.');
            redirect(base_url('/login'));
        }

        $dbCode = (string) ($u['phone_verification_code'] ?? '');
        $expires = (string) ($u['phone_verification_expires_at'] ?? '');
        if (!$dbCode || $dbCode !== $code) {
            flash_set('error', 'Código incorreto.');
            redirect(base_url('/confirmar-celular'));
        }
        if ($expires && strtotime($expires) < time()) {
            flash_set('error', 'Código expirado. Reenvie e tente novamente.');
            redirect(base_url('/confirmar-celular'));
        }

        $userModel->verifyPhone($uid);
        $userModel->updateLastLogin($uid, \client_ip());
        $user = $userModel->findById($uid);
        unset($_SESSION['pending_phone_user_id'], $_SESSION['pending_phone_masked']);
        $_SESSION['user'] = $user;
        flash_set('success', 'Celular confirmado com sucesso!');
        redirect(base_url('/'));
    }

    public function resendPhoneCode(): void
    {
        $this->validateCsrf();
        $uid = (int) ($_SESSION['pending_phone_user_id'] ?? 0);
        if ($uid <= 0) {
            redirect(base_url('/login'));
        }
        $userModel = new User();
        $u = $userModel->findById($uid);
        if (!$u) {
            unset($_SESSION['pending_phone_user_id'], $_SESSION['pending_phone_masked']);
            redirect(base_url('/login'));
        }
        $phone = (string) ($u['phone'] ?? '');
        if (!$phone) {
            flash_set('error', 'Celular não encontrado.');
            redirect(base_url('/login'));
        }

        $code = $this->generateCode();
        $ttl = (int) config('app.sms.code_ttl_minutes', 10);
        $userModel->setPhoneVerification($uid, $code, $ttl);
        $sms = new SmsService();
        $send = $sms->sendVerificationCode($this->toE164($phone), $code);
        $msg = 'Código reenviado.';
        if (!(bool) $send['success']) {
            $detail = (string) ($send['error'] ?? '');
            $msg = 'Não foi possível enviar o SMS agora. Tente novamente.'
                . ($detail ? ' Motivo: ' . $detail : '');
        } elseif (config('app.sms.driver', 'simulated') === 'simulated' && config('app.sms.show_code_in_flash', false)) {
            $msg .= ' (SIMULADO: código ' . $code . ')';
        }
        flash_set('success', $msg);
        redirect(base_url('/confirmar-celular'));
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        flash_set('success', 'Você saiu.');
        redirect(base_url('/'));
    }
}
