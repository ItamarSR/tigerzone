<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'TigerZone') ?> – TigerZone</title>
    <meta property="og:title" content="<?= htmlspecialchars($title ?? 'TigerZone') ?> – TigerZone">
    <meta property="og:image" content="<?= base_url(asset('images/og-image.png')) ?>">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>" sizes="32x32">
    <link rel="apple-touch-icon" href="<?= asset('images/apple-touch-icon.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <?= $styles ?? '' ?>
</head>
<body class="<?= !empty($stars) ? 'has-stars' : '' ?>">
    <?php if (!empty($stars)): ?>
        <canvas id="tz-stars" class="tz-stars" data-starfield="main" aria-hidden="true"></canvas>
        <script>
        window.STARFIELD_CONFIGS = window.STARFIELD_CONFIGS || {};
        window.STARFIELD_CONFIGS['main'] = {
            centerX: 0.55,
            centerY: 0.28,
            centerBias: 0.7,
            speed: 9,
            twinkle: 0.55
        };
        </script>
    <?php endif; ?>
    <?php $user = auth() ?? []; ?>
    <header class="site-header">
        <div class="container header-inner">
            <a href="<?= base_url('/') ?>" class="logo"><img src="<?= asset('images/logo.png') ?>" alt="TigerZone" class="logo-img"></a>
            <nav class="nav-main">
                <a href="<?= base_url('/') ?>">Início</a>
                <a href="<?= base_url('/jogos') ?>">Jogos</a>
                <?php if ($user): ?>
                    <a href="<?= base_url('/carteira') ?>">Carteira</a>
                    <a href="<?= base_url('/convites') ?>">Convites</a>
                    <span class="user-credits"><?= config('app.currency_display') ?> <?= number_format((float)($user['balance'] ?? 0), 2, ',', '.') ?></span>
                    <a href="<?= base_url('/logout') ?>" class="btn btn-ghost">Sair</a>
                <?php else: ?>
                    <a href="<?= base_url('/login') ?>" class="btn btn-ghost">Entrar</a>
                    <a href="<?= base_url('/registro') ?>" class="btn btn-primary">Cadastrar</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <?php if ($msg = flash('success')): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?>
        <div class="alert alert-error"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <main class="main-content">
        <?= $content ?? '' ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>TigerZone</p>
        </div>
    </footer>

    <script src="<?= asset('js/app.js') ?>"></script>
    <?php if (!empty($stars)): ?>
        <script src="<?= asset('js/starfield.js') ?>"></script>
    <?php endif; ?>
    <?= $scripts ?? '' ?>
</body>
</html>
