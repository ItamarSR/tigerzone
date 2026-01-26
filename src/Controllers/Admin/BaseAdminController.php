<?php

declare(strict_types=1);

namespace TigerZone\Controllers\Admin;

use TigerZone\Controllers\BaseController;

abstract class BaseAdminController extends BaseController
{
    protected function requireAdmin(): void
    {
        if (!\is_admin()) {
            \flash_set('error', 'Acesso restrito a administradores.');
            \redirect(\base_url('/admin'));
        }
    }
}
