<?php

declare(strict_types=1);

/**
 * TigerZone – Instalador
 * Executado quando config/installed.php não existe e URI = /install
 */

$baseDir = dirname(__DIR__);
$configDir = $baseDir . '/config';
$installedFile = $configDir . '/installed.php';
$lockFile = $baseDir . '/install.lock';

if (is_file($installedFile)) {
    header('Location: /');
    exit;
}

require $baseDir . '/vendor/autoload.php';

$defaultDb = require $configDir . '/database.php';
$app = require $configDir . '/app.php';
date_default_timezone_set($app['timezone'] ?? 'America/Sao_Paulo');

$isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$error = '';
$done = false;

if ($isPost) {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $port = (int) ($_POST['db_port'] ?? 3306);
    $database = trim($_POST['db_name'] ?? 'tigerzone');
    $username = trim($_POST['db_user'] ?? '');
    $password = (string) ($_POST['db_pass'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass = (string) ($_POST['admin_password'] ?? '');
    $adminName = trim($_POST['admin_name'] ?? 'Admin');

    if (!$host || !$database || !$username || !$adminEmail || !$adminPass) {
        $error = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'E-mail do administrador inválido.';
    } elseif (strlen($adminPass) < 6) {
        $error = 'A senha do admin deve ter no mínimo 6 caracteres.';
    } else {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$database}`");
            $sql = file_get_contents($baseDir . '/database/schema.sql');
            $pdo->exec($sql);

            $algo = PASSWORD_BCRYPT;
            $opts = ['cost' => 12];
            if (defined('PASSWORD_ARGON2ID')) {
                $algo = PASSWORD_ARGON2ID;
                $opts = ['memory_cost' => 65536, 'time_cost' => 4];
            }
            $adminHash = password_hash($adminPass, $algo, $opts);

            $stmt = $pdo->prepare('INSERT INTO admins (email, password, name) VALUES (?, ?, ?)');
            $stmt->execute([$adminEmail, $adminHash, $adminName ?: 'Admin']);

            $config = [
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ];
            $content = "<?php\n// Gerado pelo instalador TigerZone\nreturn " . var_export($config, true) . ";\n";
            if (!is_dir($configDir)) {
                mkdir($configDir, 0755, true);
            }
            file_put_contents($installedFile, $content);
            touch($lockFile);
            $done = true;
        } catch (Throwable $e) {
            $error = 'Erro: ' . $e->getMessage();
        }
    }
}

$lockExists = is_file($lockFile);
if ($lockExists && !$isPost) {
    $error = 'O instalador foi bloqueado após a instalação. Para reinstalar, remova o ficheiro install.lock e config/installed.php.';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação – TigerZone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%); min-height: 100vh; margin: 0; color: #e0e0e0; padding: 2rem; }
        .wrap { max-width: 520px; margin: 0 auto; }
        h1 { font-size: 1.75rem; margin-bottom: 0.5rem; color: #f59e0b; }
        .sub { color: #94a3b8; margin-bottom: 2rem; }
        .card { background: rgba(30,30,50,0.6); border: 1px solid rgba(245,158,11,0.3); border-radius: 12px; padding: 1.5rem; }
        label { display: block; margin-top: 1rem; margin-bottom: 0.35rem; font-weight: 500; }
        input { width: 100%; padding: 0.6rem 0.75rem; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; font-size: 1rem; }
        button { width: 100%; margin-top: 1.5rem; padding: 0.75rem; background: linear-gradient(135deg, #f59e0b, #d97706); color: #0f0f1a; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; }
        button:hover { opacity: 0.95; }
        .err { background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; }
        .ok { background: rgba(34,197,94,0.2); border: 1px solid #22c55e; color: #86efac; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .ok a { color: #f59e0b; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>TigerZone</h1>
        <p class="sub">Instalação da plataforma</p>

        <?php if ($done): ?>
            <div class="card">
                <div class="ok">
                    Instalação concluída. O instalador foi bloqueado. 
                    <a href="/">Aceder ao site</a> · 
                    <a href="/admin">Painel admin</a>
                </div>
            </div>
            <?php exit; ?>
        <?php endif; ?>

        <?php if ($lockExists && !$isPost): ?>
            <div class="card">
                <div class="err"><?= htmlspecialchars($error) ?></div>
            </div>
            <?php exit; ?>
        <?php endif; ?>

        <div class="card">
            <?php if ($error): ?>
                <div class="err"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="/install/">
                <h2 style="margin-top:0;font-size:1.1rem;">Base de dados</h2>
                <label>Host</label>
                <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? $defaultDb['host'] ?? 'localhost') ?>" required>
                <div class="grid2">
                    <div>
                        <label>Porta</label>
                        <input type="number" name="db_port" value="<?= htmlspecialchars((string)($_POST['db_port'] ?? $defaultDb['port'] ?? 3306)) ?>">
                    </div>
                    <div>
                        <label>Base de dados</label>
                        <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? $defaultDb['database'] ?? 'tigerzone') ?>" required>
                    </div>
                </div>
                <label>Utilizador</label>
                <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? $defaultDb['username'] ?? '') ?>" required>
                <label>Palavra-passe</label>
                <input type="password" name="db_pass" value="" placeholder="(deixar em branco se não alterar)">
                <p style="font-size:0.85rem;color:#94a3b8;margin-top:0.5rem;">Se já tiver config/database.php, use o mesmo utilizador. Caso contrário, indique a palavra-passe.</p>

                <h2 style="margin-top:1.5rem;font-size:1.1rem;">Administrador</h2>
                <label>E-mail</label>
                <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                <label>Nome</label>
                <input type="text" name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? 'Admin') ?>">
                <label>Palavra-passe</label>
                <input type="password" name="admin_password" required minlength="6">

                <button type="submit">Instalar</button>
            </form>
        </div>
    </div>
</body>
</html>
