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
            <input type="tel" id="phone" name="phone" required inputmode="tel" autocomplete="tel"
                   maxlength="15"
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
<script>
(function () {
    var el = document.getElementById('phone');
    if (!el) return;
    function onlyDigits(s) { return String(s || '').replace(/\D+/g, ''); }
    function formatBRPhone(d) {
        // (DD) 9XXXX-XXXX (11) ou (DD) XXXX-XXXX (10)
        if (d.length <= 2) return d;
        var dd = d.slice(0, 2);
        var rest = d.slice(2);
        if (rest.length <= 4) return '(' + dd + ') ' + rest;
        if (rest.length <= 8) return '(' + dd + ') ' + rest.slice(0, 4) + '-' + rest.slice(4);
        // 9 dígitos
        return '(' + dd + ') ' + rest.slice(0, 5) + '-' + rest.slice(5, 9);
    }
    function apply() {
        var d = onlyDigits(el.value).slice(0, 11);
        el.value = formatBRPhone(d);
    }
    el.addEventListener('input', apply);
    el.addEventListener('blur', apply);
    apply();
})();
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
