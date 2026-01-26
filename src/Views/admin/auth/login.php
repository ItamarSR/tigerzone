<?php
$title = 'Login';
ob_start();
?>
<div class="container auth-page">
    <div class="auth-card">
        <h1>Admin – Entrar</h1>
        <form method="post" action="<?= base_url('/admin/login') ?>">
            <?= \TigerZone\Core\Security::csrfField() ?>
            <label>E-mail</label>
            <input type="email" name="email" required>
            <label>Senha</label>
            <input type="password" name="password" required>
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </form>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../../layouts/admin.php'; ?>
