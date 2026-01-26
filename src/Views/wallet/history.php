<?php
$title = 'Histórico';
ob_start();
?>
<div class="container">
    <h1>Histórico de transações</h1>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Ref.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                    <td><?= htmlspecialchars($t['type']) ?></td>
                    <td><?= config('app.currency_display') ?> <?= number_format((float) $t['amount'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($t['reference'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (empty($transactions)): ?>
    <p>Nenhuma transação ainda.</p>
    <?php endif; ?>
    <p><a href="<?= base_url('/carteira') ?>">Voltar à carteira</a></p>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
