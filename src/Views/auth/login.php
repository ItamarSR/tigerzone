<?php
$title = 'Entrar';
ob_start();
?>
<div class="container auth-page">
    <div class="auth-card">
        <h1>Entrar</h1>
        <form method="post" action="<?= base_url('/login') ?>">
            <?= \TigerZone\Core\Security::csrfField() ?>
            <label>E-mail</label>
            <input type="email" name="email" required value="<?= htmlspecialchars(old('email')) ?>">
            <label>Senha</label>
            <input type="password" name="password" required>
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </form>
        <p class="auth-footer">Não tem conta? <a href="<?= base_url('/registro') ?>">Cadastre-se</a></p>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
