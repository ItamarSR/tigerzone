<?php

declare(strict_types=1);

namespace TigerZone\Controllers\Admin;

use TigerZone\Models\Settings;

class SettingsController extends BaseAdminController
{
    public function index(): void
    {
        $this->requireAdmin();
        $settings = new Settings();
        $this->view('admin.settings.index', [
            'title' => 'Configurações',
            'settings' => $settings,
        ]);
    }

    public function save(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $settings = new Settings();
        $keys = ['site_title', 'stats_online_base', 'stats_wins_base', 'stats_deposits_base', 'site_logo_url'];
        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                $settings->set($k, \TigerZone\Core\Security::sanitize((string) $_POST[$k]));
            }
        }
        \flash_set('success', 'Configurações salvas.');
        \redirect(\base_url('/admin/configuracoes'));
    }
}
