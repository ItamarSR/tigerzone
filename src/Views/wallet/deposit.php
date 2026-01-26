<?php
$title = 'Depósito';
ob_start();
?>
<div class="container">
    <h1>Depósito</h1>
    <div class="form-card">
        <form method="post" action="<?= base_url('/carteira/deposito') ?>">
            <?= \TigerZone\Core\Security::csrfField() ?>
            <label>Valor (R$ 1 – R$ 10.000)</label>
            <input type="text" name="amount" placeholder="0,00" required inputmode="decimal">
            <button type="submit" class="btn btn-primary btn-block">Depositar</button>
        </form>
    </div>
    <p><a href="<?= base_url('/carteira') ?>">Voltar à carteira</a></p>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
