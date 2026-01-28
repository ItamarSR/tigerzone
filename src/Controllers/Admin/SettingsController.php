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
        $smsEnabled = (bool) \config('app.sms.enabled', false);
        $smsDriver = (string) \config('app.sms.driver', 'simulated');
        $twSid = (string) \config('app.sms.twilio.account_sid', '');
        $twToken = (string) \config('app.sms.twilio.auth_token', '');
        $twFrom = (string) \config('app.sms.twilio.from', '');
        $zenToken = (string) \config('app.sms.zenvia.api_token', '');
        $zenFrom = (string) \config('app.sms.zenvia.from', '');
        $missing = [];
        if ($smsEnabled && $smsDriver === 'twilio') {
            if ($twSid === '') $missing[] = 'TWILIO_ACCOUNT_SID';
            if ($twToken === '') $missing[] = 'TWILIO_AUTH_TOKEN';
            if ($twFrom === '') $missing[] = 'TWILIO_FROM';
        }
        if ($smsEnabled && $smsDriver === 'zenvia') {
            if ($zenToken === '') $missing[] = 'ZENVIA_API_TOKEN';
            // ZENVIA_FROM pode ser opcional; só marca como faltando se estiver explicitamente exigido no seu provedor
        }
        $this->view('admin.settings.index', [
            'title' => 'Configurações',
            'settings' => $settings,
            'sms_status' => [
                'enabled' => $smsEnabled,
                'driver' => $smsDriver,
                'twilio' => [
                    'account_sid_set' => $twSid !== '',
                    'auth_token_set' => $twToken !== '',
                    'from_set' => $twFrom !== '',
                    'missing' => $missing,
                ],
                'zenvia' => [
                    'api_token_set' => $zenToken !== '',
                    'from_set' => $zenFrom !== '',
                    'missing' => $missing,
                ],
            ],
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
