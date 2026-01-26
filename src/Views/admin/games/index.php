<?php
$title = 'Jogos';
ob_start();
?>
<div class="container">
    <h1>Jogos</h1>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>ID</th><th>Slug</th><th>Nome</th><th>Ativo</th></tr>
            </thead>
            <tbody>
                <?php foreach ($games as $g): ?>
                <tr>
                    <td><?= (int) $g['id'] ?></td>
                    <td><?= htmlspecialchars($g['slug']) ?></td>
                    <td><?= htmlspecialchars($g['name']) ?></td>
                    <td><?= !empty($g['is_active']) ? 'Sim' : 'Nao' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../../layouts/admin.php'; ?>
