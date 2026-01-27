<?php
$title = 'Cadastro';
ob_start();
?>
<div class="container auth-page">
    <div class="auth-card">
        <h1>Cadastrar</h1>
        <form method="post" action="<?= base_url('/registro') ?>">
            <?= \TigerZone\Core\Security::csrfField() ?>
            <label>Nome</label>
            <input type="text" name="name" required value="<?= htmlspecialchars(old('name')) ?>">
            <label>Celular (WhatsApp)</label>
            <input type="tel" name="phone" required inputmode="tel" autocomplete="tel"
                   placeholder="(11) 91234-5678"
                   value="<?= htmlspecialchars(old('phone')) ?>">
            <label>E-mail</label>
            <input type="email" name="email" required value="<?= htmlspecialchars(old('email')) ?>">
            <label>Senha (mín. 6 caracteres)</label>
            <input type="password" name="password" required minlength="6">
            <label>Código de convite (opcional)</label>
            <input type="text" name="ref" value="<?= htmlspecialchars(old('ref', $ref ?? '')) ?>" placeholder="Ex: ABC123">
            <button type="submit" class="btn btn-primary btn-block">Cadastrar</button>
        </form>
        <p class="auth-footer">Já tem conta? <a href="<?= base_url('/login') ?>">Entrar</a></p>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
