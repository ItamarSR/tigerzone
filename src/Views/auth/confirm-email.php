<?php
$title = 'Confirmar e-mail';
ob_start();
$masked = $masked_email ?? '';
?>
<div class="container auth-page">
    <div class="auth-card">
        <h1>Confirmar e-mail</h1>
        <p class="lead" style="margin-top:-0.75rem;">
            Enviamos um link de confirmação para <strong><?= htmlspecialchars($masked) ?></strong>.
        </p>
        <form method="post" action="<?= base_url('/confirmar-email') ?>">
            <?= \TigerZone\Core\Security::csrfField() ?>
            <label>Código/Token</label>
            <input type="text" name="token" required autocomplete="one-time-code" placeholder="Cole aqui o token do e-mail">
            <button type="submit" class="btn btn-primary btn-block">Confirmar</button>
        </form>
        <form method="post" action="<?= base_url('/confirmar-email/reenviar') ?>" style="margin-top:0.75rem;">
            <?= \TigerZone\Core\Security::csrfField() ?>
            <button type="submit" class="btn btn-secondary btn-block">Reenviar e-mail</button>
        </form>
        <p class="auth-footer"><a href="<?= base_url('/login') ?>">Voltar</a></p>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>

