<?php
$title = 'Banimentos';
ob_start();
?>
<div class="container">
    <h1>Banimentos</h1>
    <div class="form-card" style="max-width:480px;margin-bottom:2rem">
        <h2>Novo banimento</h2>
        <form method="post" action="<?= base_url('/admin/banimentos') ?>">
            <?= \TigerZone\Core\Security::csrfField() ?>
            <label>Tipo</label>
            <select name="type" required>
                <option value="user">Usuario (ID)</option>
                <option value="ip">IP</option>
                <option value="device">Dispositivo</option>
            </select>
            <label>Alvo</label>
            <input type="text" name="target" required>
            <label>Motivo</label>
            <input type="text" name="reason">
            <button type="submit" class="btn btn-primary">Registrar</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Tipo</th><th>Alvo</th><th>Motivo</th><th>Admin</th><th>Data</th></tr>
            </thead>
            <tbody>
                <?php foreach ($bans as $b): ?>
                <tr>
                    <td><?= htmlspecialchars($b['type']) ?></td>
                    <td><?= htmlspecialchars($b['target']) ?></td>
                    <td><?= htmlspecialchars($b['reason'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($b['admin_name'] ?? '-') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../../layouts/admin.php'; ?>
