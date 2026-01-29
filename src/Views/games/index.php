<?php
$title = 'Jogos';
$stars = true;
ob_start();
?>
<div class="container">
    <h1>Jogos</h1>
    <div class="cards games-cards">
        <?php foreach ($games as $g): ?>
        <a href="<?= base_url('/jogo/' . $g['slug']) ?>" class="game-card">
            <img src="<?= asset('images/games/' . $g['slug'] . '.png') ?>" alt="<?= htmlspecialchars($g['name']) ?>" class="game-icon">
            <h3><?= htmlspecialchars($g['name']) ?></h3>
            <span class="game-cta">Jogar</span>
        </a>
        <?php endforeach; ?>
        <?php if (empty($games)): ?>
            <div class="alert alert-error">Nenhum jogo disponível no momento.</div>
        <?php endif; ?>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
