<?php
$title = 'Fortune Tiger';
ob_start();
$logoUrl = asset('images/logo.png');
$tigerUrl = asset('images/games/fortune-tiger.png');
$paytable = \TigerZone\Game\FortuneTigerSlot::getPaytable();
$currency = config('app.currency_display');
$pointsRate = 10; // 1 R$ = 10 pontos
?>
<div class="ft-page">
    <header class="ft-topbar">
        <a href="<?= base_url('/') ?>" class="ft-logo"><img src="<?= $logoUrl ?>" alt="TigerZone" class="ft-logo-img"></a>
        <h1 class="ft-title">Fortune Tiger</h1>
        <div class="ft-balance">
            <span class="ft-balance-label">Saldo</span>
            <span class="ft-balance-value"><?= $currency ?> <strong id="ft-balance"><?= number_format((float) $balance, 2, ',', '.') ?></strong></span>
            <span class="ft-points" id="ft-points"><?= (int) round($balance * $pointsRate) ?> pts</span>
        </div>
        <a href="<?= base_url('/jogos') ?>" class="ft-back">← Voltar</a>
    </header>

    <div class="ft-stage">
        <div class="ft-tiger ft-tiger-left" aria-hidden="true">
            <img src="<?= $tigerUrl ?>" alt="" class="ft-tiger-img">
        </div>

        <div class="ft-cabinet">
            <div class="ft-cabinet-frame">
                <div class="ft-reel-glass"></div>
                <div class="ft-reels" id="ft-reels">
                    <?php for ($c = 0; $c < 5; $c++): ?>
                    <div class="ft-reel ft-reel-col" data-reel="<?= $c ?>" data-column="<?= $c + 1 ?>">
                        <div class="ft-reel-strip">
                            <div class="ft-symbol-wrap"><div class="ft-symbol" data-value="1"><?= $currency ?> 1,00</div></div>
                            <div class="ft-symbol-wrap"><div class="ft-symbol" data-value="1"><?= $currency ?> 1,00</div></div>
                            <div class="ft-symbol-wrap"><div class="ft-symbol" data-value="1"><?= $currency ?> 1,00</div></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="ft-payline"></div>
            </div>
        </div>

        <div class="ft-tiger ft-tiger-right" aria-hidden="true">
            <img src="<?= $tigerUrl ?>" alt="" class="ft-tiger-img">
        </div>
    </div>

    <div class="ft-controls">
        <form id="ft-play-form" class="ft-play-form">
            <?= \TigerZone\Core\Security::csrfField() ?>
            <input type="hidden" name="game" value="fortune-tiger">
            <input type="hidden" name="columns" id="ft-columns-input" value="3">
            <div class="ft-bet-row">
                <label>Aposta (R$)</label>
                <input type="number" name="bet" id="ft-bet" min="1" max="20" step="1" value="5">
            </div>
            <div class="ft-points-row">
                <span class="ft-points-spin" id="ft-points-spin">50 pts</span> <span class="ft-points-desc">por rodada</span>
            </div>
            <div class="ft-columns-row">
                <span class="ft-columns-label">Colunas</span>
                <div class="ft-columns-btns">
                    <button type="button" class="ft-col-btn active" data-cols="3">3</button>
                    <button type="button" class="ft-col-btn" data-cols="4">4</button>
                    <button type="button" class="ft-col-btn" data-cols="5">5</button>
                </div>
            </div>
            <button type="submit" class="ft-spin-btn" id="ft-spin">GIRAR</button>
        </form>
        <div id="ft-win" class="ft-win"></div>
    </div>

    <div class="ft-bottom">
        <div class="ft-paytable">
            <span class="ft-paytable-title">Prémios (× aposta):</span>
            <?php foreach (array_reverse($paytable['values']) as $v): ?>
            <span class="ft-pay-line"><?= $currency ?> <?= $v ?>: ×3→<?= (int) $paytable['pay_3'][$v] ?>× ×4→<?= (int) $paytable['pay_4'][$v] ?>× ×5→<?= (int) $paytable['pay_5'][$v] ?>×</span>
            <?php endforeach; ?>
            <span class="ft-pay-line ft-pay-2"><?= $currency ?> 20 ×2→1×</span>
        </div>
        <div class="ft-history-compact">
            <span class="ft-history-label">Últimas</span>
            <div class="ft-history-list">
                <?php foreach (array_slice($history, 0, 5) as $h): ?>
                <span class="ft-history-item"><?= $currency ?> <?= number_format((float) $h['win'], 2, ',', '.') ?></span>
                <?php endforeach; ?>
                <?php if (empty($history)): ?>
                <span class="ft-history-item ft-history-empty">—</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
window.FT_CONFIG = {
    currency: '<?= $currency ?>',
    playUrl: '<?= base_url('/api/jogo/play') ?>',
    values: [1, 2, 3, 5, 10, 20],
    pointsRate: <?= $pointsRate ?>,
    paytable: <?= json_encode($paytable) ?>
};
</script>
<script src="<?= asset('js/fortune-tiger.js') ?>"></script>
<?php
$content = ob_get_clean();
$styles = '<link rel="stylesheet" href="' . asset('css/fortune-tiger.css') . '">';
require __DIR__ . '/../layouts/game-full.php';
?>
