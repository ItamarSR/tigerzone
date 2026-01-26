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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Cinzel:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <?= $styles ?? '' ?>
</head>
<body class="body-game-full">
    <?php if ($msg = flash('success')): ?>
        <div class="alert alert-success ft-flash"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?>
        <div class="alert alert-error ft-flash"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="ft-game-wrap">
        <?= $content ?? '' ?>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <?= $scripts ?? '' ?>
</body>
</html>
