<?php
$title = 'Início';
ob_start();
?>
<div class="container">
    <section class="hero">
        <h1>Bem-vindo ao <span class="highlight">TigerZone</span></h1>
        <p class="lead">Jogos de sorte.</p>
    </section>
    <section class="stats-bar" id="stats-bar">
        <div class="stat"><span class="stat-value" data-stat="online">—</span> <span class="stat-label">online</span></div>
        <div class="stat"><span class="stat-value" data-stat="wins">—</span> <span class="stat-label">ganhos recentes</span></div>
        <div class="stat"><span class="stat-value" data-stat="deposits">—</span> <span class="stat-label">depósitos</span></div>
    </section>
    <section class="games-grid">
        <h2>Jogos</h2>
        <div class="cards">
            <?php foreach ($games as $g): ?>
            <a href="<?= base_url('/jogo/' . $g['slug']) ?>" class="game-card">
                <img src="<?= asset('images/games/' . $g['slug'] . '.png') ?>" alt="<?= htmlspecialchars($g['name']) ?>" class="game-icon">
                <h3><?= htmlspecialchars($g['name']) ?></h3>
                <span class="game-cta">Jogar</span>
            </a>
            <?php endforeach; ?>
        </div>
        <p class="games-note"><a href="<?= base_url('/jogos') ?>">Ver todos os jogos</a></p>
    </section>
</div>
<?php
$content = ob_get_clean();
$scripts = '<script src="' . asset('js/stats.js') . '"></script>';
require __DIR__ . '/../layouts/main.php';
