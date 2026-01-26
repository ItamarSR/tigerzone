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
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../../layouts/admin.php'; ?>
