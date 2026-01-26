<?php

declare(strict_types=1);

namespace TigerZone\Controllers\Admin;

use TigerZone\Models\Admin as AdminModel;
use TigerZone\Core\Security;

class AdminsController extends BaseAdminController
{
    public function index(): void
    {
        $this->requireAdminRole(AdminModel::ROLE_ADMIN);
        $model = new AdminModel();
        $admins = $model->listAll(200);
        $this->view('admin.admins.index', [
            'title' => 'Administradores',
            'admins' => $admins,
        ]);
    }

    public function store(): void
    {
        $this->requireAdminRole(AdminModel::ROLE_ADMIN);
        $this->validateCsrf();
        $email = Security::sanitizeEmail($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $name = Security::sanitize($_POST['name'] ?? '');
        $role = (string) ($_POST['role'] ?? AdminModel::ROLE_ADMIN);
        if ($role !== AdminModel::ROLE_SUBADMIN) {
            $role = AdminModel::ROLE_ADMIN;
        }
        if (!$email || !$password || !$name) {
            \flash_set('error', 'Preencha e-mail, nome e senha.');
            \redirect(\base_url('/admin/administradores'));
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            \flash_set('error', 'E-mail inválido.');
            \redirect(\base_url('/admin/administradores'));
        }
        if (strlen($password) < 6) {
            \flash_set('error', 'A senha deve ter no mínimo 6 caracteres.');
            \redirect(\base_url('/admin/administradores'));
        }
        $model = new AdminModel();
        if ($model->findByEmail($email)) {
            \flash_set('error', 'Este e-mail já está cadastrado.');
            \redirect(\base_url('/admin/administradores'));
        }
        $hash = Security::hashPassword($password);
        $model->create($email, $hash, $name, $role);
        \flash_set('success', 'Administrador criado com sucesso.');
        \redirect(\base_url('/admin/administradores'));
    }
}
