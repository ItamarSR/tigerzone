<?php
$title = 'Usuarios';
ob_start();
?>
<div class="container">
    <h1>Usuarios</h1>
    <p>Total: <?= (int) $total ?></p>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Saldo</th>
                    <th>Criado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= config('app.currency_display') ?> <?= number_format((float)($u['balance'] ?? 0), 2, ',', '.') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                    <td><a href="<?= base_url('/admin/usuarios/' . $u['id']) ?>">Ver</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../../layouts/admin.php'; ?>
