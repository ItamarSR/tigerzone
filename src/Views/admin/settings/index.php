<?php
$title = 'Configurações';
ob_start();
?>
<div class="container">
    <h1>Configurações</h1>
    <div class="form-card" style="max-width:560px">
        <form method="post" action="<?= base_url('/admin/configuracoes') ?>">
            <?= \TigerZone\Core\Security::csrfField() ?>
            <label>Título do site</label>
            <input type="text" name="site_title" value="<?= htmlspecialchars($settings->get('site_title', 'TigerZone')) ?>">
            <label>URL do logo (opcional)</label>
            <input type="text" name="site_logo_url" value="<?= htmlspecialchars($settings->get('site_logo_url', '') ?? '') ?>">
            <label>Base contador online</label>
            <input type="number" name="stats_online_base" value="<?= (int) $settings->get('stats_online_base', '42') ?>">
            <label>Base ganhos recentes</label>
            <input type="text" name="stats_wins_base" value="<?= htmlspecialchars($settings->get('stats_wins_base', '12850')) ?>">
            <label>Base depósitos</label>
            <input type="text" name="stats_deposits_base" value="<?= htmlspecialchars($settings->get('stats_deposits_base', '89420')) ?>">
            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>
    </div>

    <?php
    $sms = $sms_status ?? ['enabled' => false, 'driver' => 'simulated', 'twilio' => ['missing' => []]];
    $missing = $sms['twilio']['missing'] ?? [];
    ?>
    <div class="form-card" style="max-width:760px; margin-top:1.25rem;">
        <h2 style="margin-top:0;">SMS (Twilio) – Status</h2>
        <p style="color:var(--text-muted); margin-top:-0.25rem;">
            O envio de código de confirmação depende de variáveis de ambiente no servidor.
        </p>
        <div class="table-wrap" style="margin:0.75rem 0 0;">
            <table class="data-table">
                <tbody>
                    <tr>
                        <th>Ativo</th>
                        <td><?= !empty($sms['enabled']) ? 'Sim' : 'Não' ?></td>
                    </tr>
                    <tr>
                        <th>Driver</th>
                        <td><?= htmlspecialchars((string) ($sms['driver'] ?? '')) ?></td>
                    </tr>
                    <tr>
                        <th>TWILIO_ACCOUNT_SID</th>
                        <td><?= !empty($sms['twilio']['account_sid_set']) ? 'OK' : 'FALTANDO' ?></td>
                    </tr>
                    <tr>
                        <th>TWILIO_AUTH_TOKEN</th>
                        <td><?= !empty($sms['twilio']['auth_token_set']) ? 'OK' : 'FALTANDO' ?></td>
                    </tr>
                    <tr>
                        <th>TWILIO_FROM</th>
                        <td><?= !empty($sms['twilio']['from_set']) ? 'OK' : 'FALTANDO' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if (!empty($sms['enabled']) && ($sms['driver'] ?? '') === 'twilio' && !empty($missing)): ?>
            <div class="alert alert-error" style="margin-top:1rem;">
                Variáveis ausentes para Twilio: <strong><?= htmlspecialchars(implode(', ', $missing)) ?></strong>.
            </div>
        <?php endif; ?>

        <p style="margin-top:1rem; color:var(--text-muted);">
            Configure no servidor (exemplos):
        </p>
        <pre style="background:rgba(0,0,0,0.25); border:1px solid var(--border); padding:0.75rem; border-radius:10px; overflow:auto; margin:0;">
SMS_DRIVER=twilio
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_FROM=+14155552671
SMS_SHOW_CODE_IN_FLASH=false
        </pre>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../../layouts/admin.php'; ?>
