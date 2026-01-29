<?php

declare(strict_types=1);

namespace TigerZone\Game;

use TigerZone\Models\Settings;
use TigerZone\Models\Wallet;

/**
 * Pool de prémios por marcos de depósitos.
 * Regras:
 * - A premiação começa quando Depósitos Gerais >= R$ 5.000,00
 * - Distribui até R$ 1.000,00 em premiações por ciclo; ao atingir, zera e começa de novo
 * - Se Depósitos (últimas 24h) > R$ 10.000,00, o ciclo dobra para R$ 4.000,00
 */
final class PrizePool
{
    private const ACTIVATION_TOTAL_DEPOSITS = 5_000.0;
    private const BOOST_24H_DEPOSITS = 10_000.0;
    private const CYCLE_BUDGET_DEFAULT = 1_000.0;
    private const CYCLE_BUDGET_BOOSTED = 4_000.0;
    private const BONUS_PER_COMBO = 200.0;
    private const KEY_CYCLE_PAID = 'prize_cycle_paid';

    private Settings $settings;
    private Wallet $wallet;

    public function __construct(?Settings $settings = null, ?Wallet $wallet = null)
    {
        $this->settings = $settings ?? new Settings();
        $this->wallet = $wallet ?? new Wallet();
    }

    public function getPool(): float
    {
        // Para UI: retorna o restante disponível no ciclo (0 se inativo)
        return $this->getRemainingInCycle();
    }

    public function getTotalDeposits(): float
    {
        return $this->wallet->totalDeposits();
    }

    public function getTotalDepositsLast24h(): float
    {
        return $this->wallet->totalDepositsLastHours(24);
    }

    public function isActive(): bool
    {
        return $this->getTotalDeposits() >= self::ACTIVATION_TOTAL_DEPOSITS;
    }

    public function shouldFacilitateCombos(): bool
    {
        return $this->isActive() && $this->getRemainingInCycle() > 0.0;
    }

    public function getCycleBudget(): float
    {
        $d24 = $this->getTotalDepositsLast24h();
        return $d24 > self::BOOST_24H_DEPOSITS ? self::CYCLE_BUDGET_BOOSTED : self::CYCLE_BUDGET_DEFAULT;
    }

    public function getPaidInCycle(): float
    {
        return $this->settings->getFloat(self::KEY_CYCLE_PAID, 0.0);
    }

    public function getRemainingInCycle(): float
    {
        if (!$this->isActive()) {
            return 0.0;
        }
        $budget = $this->getCycleBudget();
        $paid = $this->getPaidInCycle();
        if ($paid < 0) $paid = 0.0;
        if ($paid >= $budget) {
            // Se mudou o budget e ficou inconsistente, reinicia
            $this->settings->set(self::KEY_CYCLE_PAID, '0');
            return $budget;
        }
        return round(max(0.0, $budget - $paid), 2);
    }

    /** Valor pago por combinação (bônus do ciclo). */
    public function getBonusPerCombo(): float
    {
        return self::BONUS_PER_COMBO;
    }

    /**
     * Consome do orçamento do ciclo (capa o pagamento ao restante).
     * Quando atingir o budget, zera e inicia novo ciclo.
     */
    public function consumeCycle(float $requested): float
    {
        if (!$this->isActive()) {
            return 0.0;
        }
        $requested = round(max(0.0, $requested), 2);
        if ($requested <= 0) return 0.0;

        $budget = $this->getCycleBudget();
        $paid = $this->getPaidInCycle();
        if ($paid < 0) $paid = 0.0;
        if ($paid >= $budget) {
            $paid = 0.0;
        }
        $remaining = max(0.0, $budget - $paid);
        if ($remaining <= 0.0) {
            return 0.0;
        }
        $payout = min($requested, $remaining);
        $newPaid = $paid + $payout;
        if ($newPaid >= $budget - 0.0001) {
            $newPaid = 0.0; // zera e inicia do zero
        }
        $this->settings->set(self::KEY_CYCLE_PAID, (string) round($newPaid, 2));
        return round($payout, 2);
    }
}
