<?php

declare(strict_types=1);

namespace TigerZone\Game;

use TigerZone\Models\Settings;
use TigerZone\Models\Wallet;

/**
 * Pool de prémios por marcos de depósitos.
 * 10k → 1.5k, 20k → 4k, 30k → 7.5k, 40k → 12k, …
 * Dividido entre quem está a jogar (bónus por vitória).
 */
final class PrizePool
{
    private const MILESTONE_STEP = 10_000.0;
    /** Valores liberados em R$ por marco: 10k→1.5k, 20k→+2.5k, 30k→+3.5k, … */
    private const RELEASE_BASE = 1_500.0;
    private const RELEASE_INCREMENT = 1_000.0;

    private Settings $settings;
    private Wallet $wallet;

    public function __construct(?Settings $settings = null, ?Wallet $wallet = null)
    {
        $this->settings = $settings ?? new Settings();
        $this->wallet = $wallet ?? new Wallet();
    }

    public function getPool(): float
    {
        $this->releaseMilestones();
        return $this->settings->getFloat('prize_pool', 0.0);
    }

    public function getTotalDeposits(): float
    {
        return $this->wallet->totalDeposits();
    }

    public function getLastMilestone(): float
    {
        return (float) $this->settings->get('prize_pool_last_milestone', '0');
    }

    /**
     * Libera pool quando total de depósitos cruza marcos (10k, 20k, …).
     */
    public function releaseMilestones(): void
    {
        $total = $this->wallet->totalDeposits();
        $last = (float) $this->settings->get('prize_pool_last_milestone', '0');
        $pool = $this->settings->getFloat('prize_pool', 0.0);
        $next = $last + self::MILESTONE_STEP;
        if ($total < $next) {
            return;
        }
        while ($total >= $next) {
            $n = (int) ($next / self::MILESTONE_STEP);
            $release = self::RELEASE_BASE + ($n - 1) * self::RELEASE_INCREMENT;
            $pool += $release;
            $last = $next;
            $next += self::MILESTONE_STEP;
        }
        $this->settings->set('prize_pool', (string) round($pool, 2));
        $this->settings->set('prize_pool_last_milestone', (string) (int) $last);
    }

    /**
     * Concede bónus do pool a um jogador (em pontos). Retorna pontos adicionados.
     * Deduz do pool em R$; converte 1 R$ = 10 pts.
     */
    public function grantBonusToPlayer(int $userId): int
    {
        $this->releaseMilestones();
        $pool = $this->settings->getFloat('prize_pool', 0.0);
        if ($pool <= 0) {
            return 0;
        }
        $bonusReal = min($pool * 0.02, 50.0);
        if ($bonusReal < 0.01) {
            return 0;
        }
        $bonusPoints = (int) round($bonusReal * Wallet::pointsPerReal());
        $newPool = max(0.0, $pool - $bonusReal);
        $this->settings->set('prize_pool', (string) round($newPool, 2));
        $this->wallet->addPoints($userId, $bonusPoints, 'PRIZE_POOL');
        return $bonusPoints;
    }
}
