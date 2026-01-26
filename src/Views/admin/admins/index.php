<?php
$title = 'Administradores';
ob_start();
?>
<div class="container">
    <h1>Administradores</h1>
    <p>Lista de admins e subadmins. Apenas admins podem criar novos.</p>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Função</th>
                    <th>Criado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $a): ?>
                <tr>
                    <td><?= (int) $a['id'] ?></td>
                    <td><?= htmlspecialchars($a['name']) ?></td>
                    <td><?= htmlspecialchars($a['email']) ?></td>
                    <td><?= ($a['role'] ?? 'admin') === 'subadmin' ? 'Subadmin' : 'Admin' ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2 style="margin-top:2rem;">Criar novo admin ou subadmin</h2>
    <form method="post" action="<?= base_url('/admin/administradores') ?>" class="form-card" style="max-width:420px;">
        <?= \TigerZone\Core\Security::csrfField() ?>
        <label>E-mail</label>
        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <label>Nome</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        <label>Senha (mín. 6 caracteres)</label>
        <input type="password" name="password" required minlength="6">
        <label>Função</label>
        <select name="role">
            <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="subadmin" <?= ($_POST['role'] ?? '') === 'subadmin' ? 'selected' : '' ?>>Subadmin</option>
        </select>
        <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Criar</button>
    </form>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../../layouts/admin.php'; ?>
