<?php

declare(strict_types=1);

namespace TigerZone\Game;

use TigerZone\Models\Settings;
use TigerZone\Models\Wallet;

/**
 * Pool de prémios por marcos de depósitos.
 * Regras:
 * - A premiação começa quando Depósitos (desde o último reset) >= R$ 5.000,00
 * - Orçamento do ciclo: R$ 2.000,00
 * - Se Depósitos (últimas 24h) > R$ 10.000,00, soma +R$ 1.000,00 ao orçamento (R$ 3.000,00)
 * - Ao zerar o ciclo, o "depósito para premiação" é resetado e precisa atingir a meta de novo
 */
final class PrizePool
{
    private const ACTIVATION_TARGET = 5_000.0;
    private const BOOST_24H_DEPOSITS = 10_000.0;
    private const CYCLE_BUDGET_DEFAULT = 2_000.0;
    private const CYCLE_BUDGET_BOOST_ADD = 1_000.0;
    private const KEY_CYCLE_PAID = 'prize_cycle_paid';
    private const KEY_LAST_RESET_TOTAL_DEPOSITS = 'prize_last_reset_total_deposits';

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

    /** Depósitos desde o último reset do ciclo (para meta de ativação). */
    public function getDepositsSinceReset(): float
    {
        $total = $this->getTotalDeposits();
        $last = $this->settings->getFloat(self::KEY_LAST_RESET_TOTAL_DEPOSITS, 0.0);
        $v = $total - $last;
        return $v > 0 ? round($v, 2) : 0.0;
    }

    public function getActivationTarget(): float
    {
        return self::ACTIVATION_TARGET;
    }

    public function isActive(): bool
    {
        return $this->getDepositsSinceReset() >= self::ACTIVATION_TARGET;
    }

    public function shouldFacilitateCombos(): bool
    {
        // Facilita 50% enquanto o ciclo estiver ativo e houver saldo.
        return $this->isActive() && $this->getRemainingInCycle() > 0.0;
    }

    public function getCycleBudget(): float
    {
        $d24 = $this->getTotalDepositsLast24h();
        return $d24 > self::BOOST_24H_DEPOSITS
            ? self::CYCLE_BUDGET_DEFAULT + self::CYCLE_BUDGET_BOOST_ADD
            : self::CYCLE_BUDGET_DEFAULT;
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
        $startingPaid = $paid;
        $remaining = max(0.0, $budget - $paid);
        if ($remaining <= 0.0) {
            return 0.0;
        }
        $payout = min($requested, $remaining);
        $newPaid = $paid + $payout;
        $cycleCompleted = $newPaid >= $budget - 0.0001;
        if ($cycleCompleted) {
            $newPaid = 0.0; // zera e inicia do zero
            // Ao zerar o ciclo, reseta o "depósito para premiação"
            $this->settings->set(self::KEY_LAST_RESET_TOTAL_DEPOSITS, (string) round($this->getTotalDeposits(), 2));
        }
        $this->settings->set(self::KEY_CYCLE_PAID, (string) round($newPaid, 2));
        return round($payout, 2);
    }
}
