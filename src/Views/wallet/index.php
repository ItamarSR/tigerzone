<?php
$title = 'Carteira';
ob_start();
?>
<div class="container">
    <h1>Carteira</h1>
    <div class="wallet-balance">
        <span class="label">Saldo</span>
        <span class="amount"><?= config('app.currency_display') ?> <?= number_format((float) $balance, 2, ',', '.') ?></span>
    </div>
    <div class="wallet-actions">
        <a href="<?= base_url('/carteira/deposito') ?>" class="btn btn-primary">Depósito</a>
        <a href="<?= base_url('/carteira/saque') ?>" class="btn btn-secondary">Saque</a>
        <a href="<?= base_url('/carteira/historico') ?>" class="btn btn-ghost">Histórico</a>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
