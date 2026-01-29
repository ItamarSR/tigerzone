<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Admin') ?> – TigerZone Admin</title>
    <link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>" sizes="32x32">
    <link rel="apple-touch-icon" href="<?= asset('images/apple-touch-icon.png') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="container header-inner">
            <a href="<?= base_url(\is_admin() ? '/admin/dashboard' : '/admin') ?>" class="logo"><img src="<?= asset('images/logo.png') ?>" alt="TigerZone Admin" class="logo-img"></a>
            <?php if (\is_admin()): ?>
            <nav class="nav-main">
                <a href="<?= base_url('/admin/dashboard') ?>">Dashboard</a>
                <a href="<?= base_url('/admin/usuarios') ?>">Usuários</a>
                <a href="<?= base_url('/admin/jogos') ?>">Jogos</a>
                <a href="<?= base_url('/admin/ganhadores') ?>">Ganhadores</a>
                <a href="<?= base_url('/admin/convites') ?>">Convites</a>
                <a href="<?= base_url('/admin/banimentos') ?>">Banimentos</a>
                <a href="<?= base_url('/admin/estatisticas') ?>">Estatísticas</a>
                <a href="<?= base_url('/admin/propagandas') ?>">Propagandas</a>
                <?php if (admin_role() === 'admin'): ?>
                <a href="<?= base_url('/admin/administradores') ?>">Administradores</a>
                <?php endif; ?>
                <a href="<?= base_url('/admin/configuracoes') ?>">Configurações</a>
                <a href="<?= base_url('/admin/logout') ?>">Sair</a>
            </nav>
            <?php endif; ?>
        </div>
    </header>
    <?php if ($m = flash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = flash('error')): ?><div class="alert alert-error"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <main class="admin-main"><?= $content ?? '' ?></main>
    <script src="<?= asset('js/app.js') ?>"></script>
    <?= $scripts ?? '' ?>
</body>
</html>
