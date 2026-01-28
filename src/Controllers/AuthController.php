<?php

declare(strict_types=1);

namespace TigerZone\Controllers;

use TigerZone\Core\Security;
use TigerZone\Models\User;
use TigerZone\Models\Wallet;
use TigerZone\Models\Ban;
use TigerZone\Models\Invite;
use TigerZone\Mail\MailService;

class AuthController extends BaseController
{
    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        // remove zeros à esquerda comuns em discagem
        $digits = ltrim($digits, '0');
        return $digits;
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) return $email;
        [$u, $d] = explode('@', $email, 2);
        $u = (string) $u;
        $d = (string) $d;
        if (strlen($u) <= 2) {
            $uMasked = substr($u, 0, 1) . '•';
        } else {
            $uMasked = substr($u, 0, 1) . str_repeat('•', max(1, strlen($u) - 2)) . substr($u, -1);
        }
        return $uMasked . '@' . $d;
    }

    private function generateEmailToken(): string
    {
        return bin2hex(random_bytes(16));
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
        // Exige e-mail confirmado (quando disponível)
        if ((bool) config('app.email_verification.enabled', true) && $userModel->supportsEmailVerification() && empty($user['email_verified_at'])) {
            $_SESSION['pending_email_user_id'] = (int) $user['id'];
            $_SESSION['pending_email_masked'] = $this->maskEmail((string) ($user['email'] ?? ''));
            flash_set('error', 'Confirme seu e-mail para entrar.');
            redirect(base_url('/confirmar-email'));
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
        if ((bool) config('app.email_verification.enabled', true) && !$userModel->supportsEmailVerification()) {
            flash_set('error', 'Banco desatualizado: aplique a migration `database/migrations/006_email_verification.sql` (confirmação por e-mail) e tente novamente.');
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

        // Confirmação por e-mail
        if ((bool) config('app.email_verification.enabled', true) && $userModel->supportsEmailVerification()) {
            $token = $this->generateEmailToken();
            $ttl = (int) config('app.email_verification.token_ttl_minutes', 60);
            $userModel->setEmailVerification($userId, $token, $ttl);
            $_SESSION['pending_email_user_id'] = $userId;
            $_SESSION['pending_email_masked'] = $this->maskEmail($email);

            $verifyUrl = base_url('/confirmar-email/verify?token=' . urlencode($token));
            $subject = 'Confirme seu e-mail';
            $brand = (string) config('app.name', 'TigerZone');
            $html = '<p>Olá!</p>'
                . '<p>Para confirmar seu e-mail no <strong>' . htmlspecialchars($brand) . '</strong>, clique no link abaixo:</p>'
                . '<p><a href="' . htmlspecialchars($verifyUrl) . '">' . htmlspecialchars($verifyUrl) . '</a></p>'
                . '<p>Se preferir, você pode copiar e colar o token na tela de confirmação: <strong>' . htmlspecialchars($token) . '</strong></p>';

            $mail = new MailService();
            $send = $mail->send($email, $subject, $html);
            $msg = 'Conta criada! Enviamos um e-mail para confirmação.';
            if (!(bool) $send['success']) {
                $detail = (string) ($send['error'] ?? '');
                $msg = 'Conta criada! Não foi possível enviar o e-mail agora. Tente reenviar.'
                    . ($detail ? ' Motivo: ' . $detail : '');
                if ((bool) config('app.email_verification.show_token_in_flash', false)) {
                    $msg .= ' (TOKEN: ' . $token . ')';
                }
            } elseif ((bool) config('app.email_verification.show_token_in_flash', false)) {
                $msg .= ' (TOKEN: ' . $token . ')';
            }
            flash_set('success', $msg);
            redirect(base_url('/confirmar-email'));
        }

        $_SESSION['user'] = $user;
        flash_set('success', 'Conta criada com sucesso! Bónus de ' . config('app.currency_display') . ' ' . number_format(25.00, 2, ',', '.') . ' creditado.');
        redirect(base_url('/'));
    }

    public function confirmEmailForm(): void
    {
        if (auth()) {
            redirect(base_url('/'));
        }
        $uid = (int) ($_SESSION['pending_email_user_id'] ?? 0);
        if ($uid <= 0) {
            redirect(base_url('/login'));
        }
        $masked = (string) ($_SESSION['pending_email_masked'] ?? 'seu e-mail');
        $this->view('auth.confirm-email', ['title' => 'Confirmar e-mail', 'masked_email' => $masked]);
    }

    public function confirmEmailFromLink(): void
    {
        $userModel = new User();
        $token = (string) ($_GET['token'] ?? '');
        $token = trim($token);
        if ($token === '' || strlen($token) < 10) {
            flash_set('error', 'Token inválido.');
            redirect(base_url('/confirmar-email'));
        }
        $u = $userModel->findByEmailVerificationToken($token);
        if (!$u) {
            flash_set('error', 'Token inválido ou expirado.');
            redirect(base_url('/confirmar-email'));
        }
        $expires = (string) ($u['email_verification_expires_at'] ?? '');
        if ($expires && strtotime($expires) < time()) {
            flash_set('error', 'Token expirado. Reenvie e tente novamente.');
            redirect(base_url('/confirmar-email'));
        }

        $uid = (int) $u['id'];
        $userModel->verifyEmail($uid);
        $userModel->updateLastLogin($uid, \client_ip());
        $user = $userModel->findById($uid);
        unset($_SESSION['pending_email_user_id'], $_SESSION['pending_email_masked']);
        $_SESSION['user'] = $user;
        flash_set('success', 'E-mail confirmado com sucesso!');
        redirect(base_url('/'));
    }

    public function confirmEmail(): void
    {
        $this->validateCsrf();
        $uid = (int) ($_SESSION['pending_email_user_id'] ?? 0);
        if ($uid <= 0) {
            redirect(base_url('/login'));
        }
        $token = trim((string) ($_POST['token'] ?? ''));
        if ($token === '' || strlen($token) < 10) {
            flash_set('error', 'Token inválido.');
            redirect(base_url('/confirmar-email'));
        }
        $userModel = new User();
        $u = $userModel->findById($uid);
        if (!$u) {
            unset($_SESSION['pending_email_user_id'], $_SESSION['pending_email_masked']);
            redirect(base_url('/login'));
        }
        $dbToken = (string) ($u['email_verification_token'] ?? '');
        $expires = (string) ($u['email_verification_expires_at'] ?? '');
        if (!$dbToken || $dbToken !== $token) {
            flash_set('error', 'Token incorreto.');
            redirect(base_url('/confirmar-email'));
        }
        if ($expires && strtotime($expires) < time()) {
            flash_set('error', 'Token expirado. Reenvie e tente novamente.');
            redirect(base_url('/confirmar-email'));
        }

        $userModel->verifyEmail($uid);
        $userModel->updateLastLogin($uid, \client_ip());
        $user = $userModel->findById($uid);
        unset($_SESSION['pending_email_user_id'], $_SESSION['pending_email_masked']);
        $_SESSION['user'] = $user;
        flash_set('success', 'E-mail confirmado com sucesso!');
        redirect(base_url('/'));
    }

    public function resendEmailVerification(): void
    {
        $this->validateCsrf();
        $uid = (int) ($_SESSION['pending_email_user_id'] ?? 0);
        if ($uid <= 0) {
            redirect(base_url('/login'));
        }
        $userModel = new User();
        if (!$userModel->supportsEmailVerification()) {
            flash_set('error', 'Banco desatualizado: aplique a migration `database/migrations/006_email_verification.sql`.');
            redirect(base_url('/login'));
        }
        $u = $userModel->findById($uid);
        if (!$u) {
            unset($_SESSION['pending_email_user_id'], $_SESSION['pending_email_masked']);
            redirect(base_url('/login'));
        }
        $email = (string) ($u['email'] ?? '');
        if ($email === '') {
            flash_set('error', 'E-mail não encontrado.');
            redirect(base_url('/login'));
        }

        $token = $this->generateEmailToken();
        $ttl = (int) config('app.email_verification.token_ttl_minutes', 60);
        $userModel->setEmailVerification($uid, $token, $ttl);
        $_SESSION['pending_email_masked'] = $this->maskEmail($email);

        $verifyUrl = base_url('/confirmar-email/verify?token=' . urlencode($token));
        $subject = 'Confirme seu e-mail';
        $brand = (string) config('app.name', 'TigerZone');
        $html = '<p>Olá!</p>'
            . '<p>Para confirmar seu e-mail no <strong>' . htmlspecialchars($brand) . '</strong>, clique no link abaixo:</p>'
            . '<p><a href="' . htmlspecialchars($verifyUrl) . '">' . htmlspecialchars($verifyUrl) . '</a></p>'
            . '<p>Token: <strong>' . htmlspecialchars($token) . '</strong></p>';

        $mail = new MailService();
        $send = $mail->send($email, $subject, $html);
        $msg = 'E-mail reenviado.';
        if (!(bool) $send['success']) {
            $detail = (string) ($send['error'] ?? '');
            $msg = 'Não foi possível enviar o e-mail agora. Tente novamente.'
                . ($detail ? ' Motivo: ' . $detail : '');
            if ((bool) config('app.email_verification.show_token_in_flash', false)) {
                $msg .= ' (TOKEN: ' . $token . ')';
            }
        } elseif ((bool) config('app.email_verification.show_token_in_flash', false)) {
            $msg .= ' (TOKEN: ' . $token . ')';
        }
        flash_set('success', $msg);
        redirect(base_url('/confirmar-email'));
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        flash_set('success', 'Você saiu.');
        redirect(base_url('/'));
    }
}
