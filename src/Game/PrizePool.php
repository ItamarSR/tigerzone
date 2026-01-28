<?php

declare(strict_types=1);

namespace TigerZone\Game;

use TigerZone\Models\Settings;
use TigerZone\Models\Wallet;

/**
 * Pool de prémios por marcos de depósitos.
 * A cada R$ 1.000,00 em depósitos totais, libera R$ 200,00 para premiação.
 * A premiação é concedida aleatoriamente a quem fizer combinações (vitórias).
 */
final class PrizePool
{
    private const MILESTONE_STEP = 1_000.0;
    private const RELEASE_PER_MILESTONE = 200.0;
    private const BONUS_AMOUNT = 200.0;
    private const BONUS_CHANCE_PERCENT = 5; // 5% de chance por vitória

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
     * Libera pool quando total de depósitos cruza marcos (1k, 2k, 3k, …).
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
            $pool += self::RELEASE_PER_MILESTONE;
            $last = $next;
            $next += self::MILESTONE_STEP;
        }
        $this->settings->set('prize_pool', (string) round($pool, 2));
        $this->settings->set('prize_pool_last_milestone', (string) (int) $last);
    }

    /**
     * Concede bónus do pool a um jogador (em R$). Retorna o valor creditado.
     */
    public function grantBonusToPlayer(int $userId): float
    {
        $this->releaseMilestones();
        $pool = $this->settings->getFloat('prize_pool', 0.0);
        if ($pool < self::BONUS_AMOUNT) {
            return 0.0;
        }
        // Premiação aleatória (somente quando há saldo no pool)
        $roll = random_int(1, 100);
        if ($roll > self::BONUS_CHANCE_PERCENT) {
            return 0.0;
        }
        $bonusReal = self::BONUS_AMOUNT;
        $newPool = max(0.0, $pool - $bonusReal);
        $this->settings->set('prize_pool', (string) round($newPool, 2));
        $this->wallet->add($userId, round($bonusReal, 2), 'bonus', 'PRIZE_POOL', ['source' => 'prize_pool']);
        return round($bonusReal, 2);
    }
}
