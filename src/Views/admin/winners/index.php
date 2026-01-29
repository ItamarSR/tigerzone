<?php
$title = 'Ganhadores';
ob_start();
?>
<div class="container">
    <h1>Ganhadores</h1>
    <p>Total de registos: <strong><?= (int) $total ?></strong></p>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Jogador</th>
                    <th>E-mail</th>
                    <th>Jogo</th>
                    <th>Aposta</th>
                    <th>Ganho</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($winners as $w): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($w['created_at'])) ?></td>
                    <td><?= htmlspecialchars($w['user_name']) ?></td>
                    <td><?= htmlspecialchars($w['user_email']) ?></td>
                    <td><?= htmlspecialchars($w['game_name']) ?></td>
                    <td><?= config('app.currency_display') ?> <?= number_format((float) $w['bet'], 2, ',', '.') ?></td>
                    <td><strong><?= config('app.currency_display') ?> <?= number_format((float) $w['win'], 2, ',', '.') ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="admin-pagination" style="margin-top:1.5rem;">
        <?php if ($page > 1): ?>
            <a href="<?= base_url('/admin/ganhadores?page=' . ($page - 1)) ?>" class="btn btn-secondary">← Anterior</a>
        <?php endif; ?>
        <span style="margin:0 1rem;">Página <?= (int) $page ?> de <?= (int) $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
            <a href="<?= base_url('/admin/ganhadores?page=' . ($page + 1)) ?>" class="btn btn-secondary">Seguinte →</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php if (empty($winners)): ?>
    <p>Nenhum ganhador registado.</p>
    <?php endif; ?>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../../layouts/admin.php'; ?>
