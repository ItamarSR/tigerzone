<?php
$title = 'Página não encontrada';
ob_start();
?>
<div class="container text-center py-10">
    <h1 class="text-4xl font-bold mb-4">404</h1>
    <p class="text-xl text-gray-400 mb-6">Página não encontrada.</p>
    <a href="<?= base_url('/') ?>" class="btn btn-primary">Voltar ao início</a>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
