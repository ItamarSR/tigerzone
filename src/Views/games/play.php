<?php
$title = $game['name'] ?? 'Jogo';
ob_start();
?>
<div class="container game-play">
    <h1><?= htmlspecialchars($game['name']) ?></h1>
    <div class="game-area">
        <div class="game-sidebar">
            <div class="balance-display">Saldo: <?= config('app.currency_display') ?> <span id="game-balance"><?= number_format((float) $balance, 2, ',', '.') ?></span></div>
            <form id="play-form" class="play-form">
                <?= \TigerZone\Core\Security::csrfField() ?>
                <input type="hidden" name="game" value="<?= htmlspecialchars($game['slug']) ?>">
                <label>Valor da aposta</label>
                <input type="number" name="bet" id="bet-amount" min="0.5" max="1000" step="0.5" value="5">
                <button type="submit" class="btn btn-primary btn-block" id="play-btn">Jogar</button>
            </form>
            <div id="last-result" class="last-result"></div>
        </div>
        <div class="game-stage">
            <img src="<?= asset('images/games/' . $game['slug'] . '.png') ?>" alt="<?= htmlspecialchars($game['name']) ?>" class="game-stage-icon">
            <div class="game-slot"><?= htmlspecialchars($game['name']) ?></div>
        </div>
    </div>
    <h2>Últimas jogadas</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Data</th><th>Aposta</th><th>Ganho</th></tr>
            </thead>
            <tbody>
                <?php foreach ($history as $h): ?>
                <tr>
                    <td><?= date('d/m H:i', strtotime($h['created_at'])) ?></td>
                    <td><?= config('app.currency_display') ?> <?= number_format((float) $h['bet'], 2, ',', '.') ?></td>
                    <td><?= config('app.currency_display') ?> <?= number_format((float) $h['win'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (empty($history)): ?>
    <p>Nenhuma jogada ainda.</p>
    <?php endif; ?>
</div>
<script>
document.getElementById('play-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('play-btn');
    const balanceEl = document.getElementById('game-balance');
    const resultEl = document.getElementById('last-result');
    const form = e.target;
    btn.disabled = true;
    resultEl.textContent = '...';
    const fd = new FormData(form);
    try {
        const r = await fetch('<?= base_url('/api/jogo/play') ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        const data = await r.json();
        if (data.error) {
            resultEl.textContent = data.error;
            resultEl.className = 'last-result error';
        } else {
            const win = parseFloat(data.win) || 0;
            resultEl.textContent = win > 0 ? 'Ganhou <?= config('app.currency_display') ?> ' + win.toFixed(2) + '!' : 'Tente novamente!';
            resultEl.className = 'last-result ' + (win > 0 ? 'win' : '');
            balanceEl.textContent = (parseFloat(data.balance) || 0).toFixed(2).replace('.', ',');
        }
    } catch (err) {
        resultEl.textContent = 'Erro. Tente novamente.';
        resultEl.className = 'last-result error';
    }
    btn.disabled = false;
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
