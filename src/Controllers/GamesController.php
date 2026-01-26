<?php

declare(strict_types=1);

namespace TigerZone\Controllers;

use TigerZone\Models\Game;
use TigerZone\Models\Wallet;
use TigerZone\Models\Ban;
use TigerZone\Game\FortuneTigerSlot;

class GamesController extends BaseController
{
    private function requireAuth(): void
    {
        if (!auth()) {
            flash_set('error', 'Faça login para jogar.');
            redirect(base_url('/login'));
        }
        $ban = new Ban();
        $u = auth();
        if ($ban->isBanned((int) $u['id'], \client_ip(), \TigerZone\Core\Security::fingerprint())) {
            unset($_SESSION['user']);
            flash_set('error', 'Acesso bloqueado.');
            redirect(base_url('/login'));
        }
    }

    public function index(): void
    {
        $gameModel = new Game();
        $games = $gameModel->listActive();
        $this->view('games.index', ['title' => 'Jogos', 'games' => $games]);
    }

    public function fortuneTiger(): void
    {
        $this->requireAuth();
        $this->fortuneTigerPage();
    }

    public function fortuneDragon(): void
    {
        $this->requireAuth();
        $this->gamePage('fortune-dragon', 'Fortune Dragon');
    }

    public function fortuneOx(): void
    {
        $this->requireAuth();
        $this->gamePage('fortune-ox', 'Fortune Ox');
    }

    private function fortuneTigerPage(): void
    {
        $gameModel = new Game();
        $game = $gameModel->findBySlug('fortune-tiger');
        if (!$game) {
            flash_set('error', 'Jogo não encontrado.');
            redirect(base_url('/jogos'));
        }
        $user = auth();
        $wallet = new Wallet();
        $balance = $wallet->getBalance((int) $user['id']);
        $history = $gameModel->historyByUserAndGame((int) $user['id'], (int) $game['id'], 20);
        $this->view('games.fortune-tiger', [
            'title' => 'Fortune Tiger',
            'game' => $game,
            'balance' => $balance,
            'history' => $history,
        ]);
    }

    private function gamePage(string $slug, string $name): void
    {
        $gameModel = new Game();
        $game = $gameModel->findBySlug($slug);
        if (!$game) {
            flash_set('error', 'Jogo não encontrado.');
            redirect(base_url('/jogos'));
        }
        $user = auth();
        $wallet = new Wallet();
        $balance = $wallet->getBalance((int) $user['id']);
        $history = $gameModel->historyByUserAndGame((int) $user['id'], (int) $game['id'], 20);
        $this->view('games.play', [
            'title' => $name,
            'game' => $game,
            'balance' => $balance,
            'history' => $history,
        ]);
    }

    /**
     * API: simula uma jogada (bet + win aleatório).
     */
    public function play(): void
    {
        $this->requireAuth();
        if ($this->wantsJson()) {
            $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!\TigerZone\Core\Security::validateCsrf($token)) {
                $this->json(['error' => 'CSRF inválido'], 403);
            }
        } else {
            $this->validateCsrf();
        }
        $gameSlug = \TigerZone\Core\Security::sanitize($_POST['game'] ?? '');
        $bet = (float) ($_POST['bet'] ?? 0);
        $columns = (int) ($_POST['columns'] ?? 3);
        if (!$gameSlug) {
            if ($this->wantsJson()) {
                $this->json(['error' => 'Jogo inválido'], 400);
            }
            redirect(base_url('/jogos'));
        }
        $betMin = 1.0;
        $betMax = 20.0;
        if ($gameSlug === 'fortune-tiger' && ($bet < $betMin || $bet > $betMax)) {
            if ($this->wantsJson()) {
                $this->json(['error' => 'Aposta deve ser entre R$ ' . number_format($betMin, 0, ',', '') . ' e R$ ' . number_format($betMax, 0, ',', '')], 400);
            } else {
                flash_set('error', 'Aposta deve ser entre R$ 1 e R$ 20.');
                redirect(base_url('/jogo/fortune-tiger'));
            }
        } elseif ($gameSlug !== 'fortune-tiger' && ($bet <= 0 || $bet > 1000)) {
            if ($this->wantsJson()) {
                $this->json(['error' => 'Valor inválido'], 400);
            }
            flash_set('error', 'Valor inválido.');
            redirect(base_url('/jogos'));
        }
        $gameModel = new Game();
        $game = $gameModel->findBySlug($gameSlug);
        if (!$game) {
            if ($this->wantsJson()) {
                $this->json(['error' => 'Jogo não encontrado'], 404);
            }
            redirect(base_url('/jogos'));
        }
        $user = auth();
        $wallet = new Wallet();
        $balance = $wallet->getBalance((int) $user['id']);
        if ($balance < $bet) {
            if ($this->wantsJson()) {
                $this->json(['error' => 'Saldo insuficiente', 'balance' => $balance], 400);
            }
            flash_set('error', 'Saldo insuficiente.');
            redirect(base_url('/jogo/' . $gameSlug));
        }
        $before = $balance;
        $sub = $wallet->subtract((int) $user['id'], $bet, 'bet', 'GAME:' . $gameSlug, ['game_id' => $game['id']]);
        if (!$sub['success']) {
            if ($this->wantsJson()) {
                $this->json(['error' => $sub['error'] ?? 'Erro', 'balance' => $balance], 400);
            }
            redirect(base_url('/jogo/' . $gameSlug));
        }
        $after = $sub['new_balance'];
        $reels = null;
        $mult = 0.0;
        $win = 0.0;

        if ($gameSlug === 'fortune-tiger') {
            $columns = max(3, min(5, $columns));
            $slot = new FortuneTigerSlot();
            $result = $slot->spin($bet, $columns);
            $win = $result['win'];
            $mult = $result['multiplier'];
            $reels = $result['reels'];
            if ($win > 0) {
                $add = $wallet->add((int) $user['id'], $win, 'win', 'GAME:' . $gameSlug, ['game_id' => $game['id']]);
                $after = $add['new_balance'];
            }
            $gameModel->logPlay((int) $user['id'], (int) $game['id'], $bet, $win, $before, $after, [
                'multiplier' => $mult,
                'reels' => $reels,
            ]);
        } else {
            $mult = (float) (rand(0, 100) <= 35 ? rand(15, 50) / 10 : 0);
            $win = round($bet * $mult, 2);
            if ($win > 0) {
                $add = $wallet->add((int) $user['id'], $win, 'win', 'GAME:' . $gameSlug, ['game_id' => $game['id']]);
                $after = $add['new_balance'];
            }
            $gameModel->logPlay((int) $user['id'], (int) $game['id'], $bet, $win, $before, $after, ['multiplier' => $mult]);
        }

        $_SESSION['user']['balance'] = $after;
        if ($this->wantsJson()) {
            $payload = ['win' => $win, 'balance' => $after, 'multiplier' => $mult];
            if ($reels !== null) {
                $payload['reels'] = $reels;
            }
            $this->json($payload);
        }
        flash_set('success', $win > 0 ? 'Você ganhou ' . config('app.currency_display') . ' ' . number_format($win, 2, ',', '.') . '!' : 'Tente novamente!');
        redirect(base_url('/jogo/' . $gameSlug));
    }
}
