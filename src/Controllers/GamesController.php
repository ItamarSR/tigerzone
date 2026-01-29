<?php

declare(strict_types=1);

namespace TigerZone\Controllers;

use TigerZone\Models\Game;
use TigerZone\Models\Wallet;
use TigerZone\Models\Ban;
use TigerZone\Models\Settings;
use TigerZone\Game\FortuneTigerSlot;
use TigerZone\Game\PrizePool;

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
        $prizePool = new PrizePool();
        $pool = $prizePool->getPool();
        $totalDeposits = $prizePool->getTotalDeposits();
        $totalDeposits24h = $prizePool->getTotalDepositsLast24h();
        $settings = new Settings();
        $adLeft = (string) ($settings->get('ad_left_file', '') ?? '');
        $adRight = (string) ($settings->get('ad_right_file', '') ?? '');
        $this->view('games.fortune-tiger', [
            'title' => 'Fortune Tiger',
            'game' => $game,
            'balance' => $balance,
            'history' => $history,
            'prize_pool' => $pool,
            'total_deposits' => $totalDeposits,
            'total_deposits_24h' => $totalDeposits24h,
            'ad_left' => $adLeft,
            'ad_right' => $adRight,
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

        if ($gameSlug === 'fortune-tiger') {
            $columns = max(3, min(5, $columns));

            // Multiplicador (aposta): 3 colunas = escolhido (1–40); 4 colunas = 50; 5 colunas = 100
            $betMin = 1.0;
            $betMax = 40.0;
            if ($columns === 4) {
                $betReais = 50.0;
            } elseif ($columns === 5) {
                $betReais = 100.0;
            } else {
                $betReais = round((float) $bet, 2);
                if ($betReais < $betMin || $betReais > $betMax) {
                    if ($this->wantsJson()) {
                        $this->json(['error' => 'Multiplicador deve ser entre R$ 1,00 e R$ 40,00.'], 400);
                    }
                    flash_set('error', 'Multiplicador deve ser entre R$ 1,00 e R$ 40,00.');
                    redirect(base_url('/jogo/fortune-tiger'));
                }
            }

            $beforeBalance = $wallet->getBalance((int) $user['id']);
            if ($beforeBalance < $betReais) {
                if ($this->wantsJson()) {
                    $this->json(['error' => 'Saldo insuficiente', 'balance' => $beforeBalance], 400);
                }
                flash_set('error', 'Saldo insuficiente.');
                redirect(base_url('/jogo/fortune-tiger'));
            }
            
            $sub = $wallet->subtract((int) $user['id'], $betReais, 'bet', 'GAME:fortune-tiger', [
                'game_id' => (int) $game['id'],
                'columns' => $columns,
                'bet_multiplier' => $betReais,
            ]);
            if (!$sub['success']) {
                if ($this->wantsJson()) {
                    $this->json(['error' => $sub['error'] ?? 'Erro', 'balance' => $beforeBalance], 400);
                }
                redirect(base_url('/jogo/fortune-tiger'));
            }
            $afterBalance = $sub['new_balance'];
            $slot = new FortuneTigerSlot();
            $prizePool = new PrizePool();
            $easyMode = $prizePool->shouldFacilitateCombos();

            $result = $slot->spinRoulette($columns, $easyMode);
            $reels = $result['reels'] ?? [];
            // Combinação: 3 ou mais valores iguais na linha do meio (mesmo com 4/5 colunas)
            $midVals = [];
            foreach ($reels as $col) {
                $midVals[] = (int) ($col[1] ?? 0);
            }
            $counts = array_count_values($midVals);
            arsort($counts, SORT_NUMERIC);
            $maxCount = (int) (reset($counts) ?: 0);
            $candidates = [];
            foreach ($counts as $val => $cnt) {
                if ((int) $cnt === $maxCount) {
                    $candidates[] = (int) $val;
                } else {
                    break;
                }
            }
            rsort($candidates, SORT_NUMERIC); // se empatar, usa o maior prêmio
            $basePrize = ($maxCount >= 3 && !empty($candidates)) ? (int) $candidates[0] : 0;
            $isCombo = $basePrize > 0;

            $requestedWin = $basePrize > 0 ? round(((float) $basePrize) * $betReais, 2) : 0.0;
            // Ganho base do jogo NÃO é limitado pelo ciclo de premiação
            $winReais = $requestedWin;
            $mult = (float) $betReais;
            // Bônus do ciclo de premiação (limitado ao orçamento)
            $poolBonus = 0.0;
            if ($winReais > 0) {
                // Adiciona ganho em R$ diretamente ao saldo (balance), não aos pontos
                $addResult = $wallet->add((int) $user['id'], $winReais, 'win', 'GAME:fortune-tiger', ['game_id' => $game['id']]);
                $afterBalance = $addResult['new_balance'];
            }
            if ($isCombo && $prizePool->isActive()) {
                $poolBonus = $prizePool->consumeCycle($prizePool->getBonusPerCombo());
                if ($poolBonus > 0) {
                    $addBonus = $wallet->add((int) $user['id'], $poolBonus, 'bonus', 'PRIZE_CYCLE', ['source' => 'prize_cycle', 'game_id' => $game['id']]);
                    $afterBalance = $addBonus['new_balance'];
                }
            }
            $gameModel->logPlay((int) $user['id'], (int) $game['id'], (float) $betReais, (float) $winReais, (float) $beforeBalance, (float) $afterBalance, [
                'multiplier' => $mult,
                'reels' => $reels,
                'pool_bonus' => $poolBonus,
                'columns' => $columns,
                'bet_multiplier' => $betReais,
                'combo' => $isCombo,
                'base_prize' => $basePrize,
                'requested_win' => $requestedWin,
                'easy_mode' => $easyMode,
            ]);
            $_SESSION['user']['balance'] = $afterBalance;
            if ($this->wantsJson()) {
                $this->json([
                    'win' => $winReais, // Retorna ganho em R$
                    'balance' => $afterBalance,
                    'multiplier' => $mult,
                    'pool_bonus' => $poolBonus,
                    'prize_pool' => $prizePool->getPool(),
                    'reels' => $reels,
                ]);
            }
            $currency = config('app.currency_display');
            flash_set('success', $winReais > 0
                ? 'Ganhou ' . $currency . ' ' . number_format($winReais, 2, ',', '.') . ($poolBonus > 0 ? ' + ' . $currency . ' ' . number_format((float) $poolBonus, 2, ',', '.') . ' bónus pool!' : '!')
                : 'Tente novamente!');
            redirect(base_url('/jogo/fortune-tiger'));
        }

        $balance = $wallet->getBalance((int) $user['id']);
        if ($bet <= 0 || $bet > 1000) {
            if ($this->wantsJson()) {
                $this->json(['error' => 'Valor inválido'], 400);
            }
            flash_set('error', 'Valor inválido.');
            redirect(base_url('/jogos'));
        }
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

        $mult = (float) (rand(0, 100) <= 35 ? rand(15, 50) / 10 : 0);
        $win = round($bet * $mult, 2);
        if ($win > 0) {
            $add = $wallet->add((int) $user['id'], $win, 'win', 'GAME:' . $gameSlug, ['game_id' => $game['id']]);
            $after = $add['new_balance'];
        }
        $gameModel->logPlay((int) $user['id'], (int) $game['id'], $bet, $win, $before, $after, ['multiplier' => $mult]);

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
