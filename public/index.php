<?php

declare(strict_types=1);

/**
 * TigerZone – Front Controller
 */

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../src/Core/helpers.php';

$configApp = require __DIR__ . '/../config/app.php';

date_default_timezone_set($configApp['timezone'] ?? 'America/Sao_Paulo');

session_start();

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = (string) parse_url($requestUri, PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

$installed = is_file(__DIR__ . '/../config/installed.php');
if (!$installed) {
    if (strpos($path, '/install') === 0) {
        require __DIR__ . '/../install/bootstrap.php';
        exit;
    }
    header('Location: /install/');
    exit;
}

$dbConfig = require __DIR__ . '/../config/installed.php';
\TigerZone\Core\Database::config($dbConfig);

$router = new \TigerZone\Core\Router();

// Rotas públicas
$router->get('/', 'HomeController@index');
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/registro', 'AuthController@registerForm');
$router->post('/registro', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');

// Stats simuladas (AJAX)
$router->get('/api/stats', 'StatsController@index');

// Área logada
$router->get('/carteira', 'WalletController@index');
$router->get('/carteira/deposito', 'WalletController@depositForm');
$router->post('/carteira/deposito', 'WalletController@deposit');
$router->get('/carteira/saque', 'WalletController@withdrawForm');
$router->post('/carteira/saque', 'WalletController@withdraw');
$router->get('/carteira/historico', 'WalletController@history');

$router->get('/convites', 'InviteController@index');
$router->post('/convites/gerar', 'InviteController@generate');
$router->get('/convite/{code}', 'InviteController@landing');

$router->get('/jogos', 'GamesController@index');
$router->get('/jogo/fortune-tiger', 'GamesController@fortuneTiger');
$router->get('/jogo/fortune-dragon', 'GamesController@fortuneDragon');
$router->get('/jogo/fortune-ox', 'GamesController@fortuneOx');
$router->post('/api/jogo/play', 'GamesController@play');

// Admin
$router->get('/admin', 'Admin\AuthController@loginForm');
$router->post('/admin/login', 'Admin\AuthController@login');
$router->get('/admin/logout', 'Admin\AuthController@logout');
$router->get('/admin/dashboard', 'Admin\DashboardController@index');
$router->get('/admin/usuarios', 'Admin\UsersController@index');
$router->get('/admin/usuarios/{id}', 'Admin\UsersController@show');
$router->post('/admin/usuarios/{id}/ban', 'Admin\UsersController@ban');
$router->post('/admin/usuarios/{id}/unban', 'Admin\UsersController@unban');
$router->get('/admin/jogos', 'Admin\GamesController@index');
$router->get('/admin/convites', 'Admin\InvitesController@index');
$router->get('/admin/banimentos', 'Admin\BansController@index');
$router->post('/admin/banimentos', 'Admin\BansController@store');
$router->get('/admin/estatisticas', 'Admin\StatsController@index');
$router->get('/admin/configuracoes', 'Admin\SettingsController@index');
$router->post('/admin/configuracoes', 'Admin\SettingsController@save');
$router->get('/admin/administradores', 'Admin\AdminsController@index');
$router->post('/admin/administradores', 'Admin\AdminsController@store');
$router->get('/admin/ganhadores', 'Admin\WinnersController@index');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$router->dispatch($method, $uri);
