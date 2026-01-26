<?php

declare(strict_types=1);

namespace TigerZone\Controllers\Admin;

use TigerZone\Core\Security;
use TigerZone\Models\Admin as AdminModel;

class AuthController extends BaseAdminController
{
    public function loginForm(): void
    {
        if (is_admin()) {
            \redirect(\base_url('/admin/dashboard'));
        }
        $this->view('admin.auth.login', ['title' => 'Admin – Login']);
    }

    public function login(): void
    {
        $this->validateCsrf();
        $email = Security::sanitizeEmail($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$email || !$password) {
            \flash_set('error', 'E-mail e senha obrigatórios.');
            \redirect(\base_url('/admin'));
        }
        $model = new AdminModel();
        $admin = $model->findByEmail($email);
        if (!$admin || !Security::verifyPassword($password, $admin['password'])) {
            \flash_set('error', 'Credenciais inválidas.');
            \redirect(\base_url('/admin'));
        }
        $_SESSION['admin'] = $admin;
        \redirect(\base_url('/admin/dashboard'));
    }

    public function logout(): void
    {
        unset($_SESSION['admin']);
        \redirect(\base_url('/admin'));
    }
}
