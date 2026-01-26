<?php
$title = 'Saque';
ob_start();
?>
<div class="container">
    <h1>Saque</h1>
    <p>Saldo disponível: <strong><?= config('app.currency_display') ?> <?= number_format((float) $balance, 2, ',', '.') ?></strong></p>
    <div class="form-card">
        <form method="post" action="<?= base_url('/carteira/saque') ?>">
            <?= \TigerZone\Core\Security::csrfField() ?>
            <label>Valor</label>
            <input type="text" name="amount" placeholder="0,00" required inputmode="decimal">
            <button type="submit" class="btn btn-primary btn-block">Sacar</button>
        </form>
    </div>
    <p><a href="<?= base_url('/carteira') ?>">Voltar à carteira</a></p>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
