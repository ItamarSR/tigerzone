<?php
$title = 'Confirmar celular';
ob_start();
$masked = $masked_phone ?? '';
?>
<div class="container auth-page">
    <div class="auth-card">
        <h1>Confirmar celular</h1>
        <p class="lead" style="margin-top:-0.75rem;">Enviamos um código para <strong><?= htmlspecialchars($masked) ?></strong>.</p>
        <form method="post" action="<?= base_url('/confirmar-celular') ?>">
            <?= \TigerZone\Core\Security::csrfField() ?>
            <label>Código (6 dígitos)</label>
            <input type="text" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="8" placeholder="000000">
            <button type="submit" class="btn btn-primary btn-block">Confirmar</button>
        </form>
        <form method="post" action="<?= base_url('/confirmar-celular/reenviar') ?>" style="margin-top:0.75rem;">
            <?= \TigerZone\Core\Security::csrfField() ?>
            <button type="submit" class="btn btn-secondary btn-block">Reenviar código</button>
        </form>
        <p class="auth-footer"><a href="<?= base_url('/login') ?>">Voltar</a></p>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>

