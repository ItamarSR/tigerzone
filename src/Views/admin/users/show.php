<?php
$title = 'Usuario';
ob_start();
$uid = (int) $user['id'];
?>
<div class="container">
    <h1>Usuario #<?= $uid ?></h1>
    <p><strong>Nome:</strong> <?= htmlspecialchars($user['name']) ?></p>
    <p><strong>E-mail:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p><strong>Saldo:</strong> <?= config('app.currency_display') ?> <?= number_format((float)($user['balance'] ?? 0), 2, ',', '.') ?></p>
    <p><strong>Codigo convite:</strong> <?= htmlspecialchars($user['invite_code']) ?></p>
    <p><strong>IP:</strong> <?= htmlspecialchars($user['ip'] ?? '-') ?></p>
    <?php if ($isBanned): ?>
    <p class="banned">Usuario banido.</p>
    <form method="post" action="<?= base_url('/admin/usuarios/' . $uid . '/unban') ?>" style="display:inline">
        <?= \TigerZone\Core\Security::csrfField() ?>
        <button type="submit" class="btn btn-secondary">Desbanir</button>
    </form>
    <?php else: ?>
    <form method="post" action="<?= base_url('/admin/usuarios/' . $uid . '/ban') ?>">
        <?= \TigerZone\Core\Security::csrfField() ?>
        <label>Motivo</label>
        <input type="text" name="reason" placeholder="Banimento manual">
        <button type="submit" class="btn btn-primary">Banir</button>
    </form>
    <?php endif; ?>
    <p><a href="<?= base_url('/admin/usuarios') ?>">Voltar</a></p>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../../layouts/admin.php'; ?>
