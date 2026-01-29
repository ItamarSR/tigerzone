<?php

declare(strict_types=1);

namespace TigerZone\Game;

/**
 * Motor do slot Fortune Tiger.
 * Símbolos = valores R$ (1, 2, 3, 5, 10, 20). 3, 4 ou 5 colunas. 1 payline (meio).
 */
final class FortuneTigerSlot
{
    /** Valores possíveis nos rolos (R$). Podem ser personalizados via config. */
    public const VALUES = [1, 2, 3, 5, 10, 20];

    /** Pesos para RNG (maior = mais frequente) */
    private const WEIGHTS = [
        1 => 20,
        2 => 16,
        3 => 12,
        5 => 10,
        10 => 6,
        20 => 4,
    ];

    /** Multiplicador × aposta: 3 iguais na payline */
    private const PAY_3 = [
        1 => 2.0,
        2 => 2.0,
        3 => 3.0,
        5 => 4.0,
        10 => 5.0,
        20 => 10.0,
    ];

    /** 4 iguais (quando colunas >= 4) */
    private const PAY_4 = [
        1 => 3.0,
        2 => 4.0,
        3 => 6.0,
        5 => 8.0,
        10 => 12.0,
        20 => 25.0,
    ];

    /** 5 iguais (quando colunas = 5) */
    private const PAY_5 = [
        1 => 5.0,
        2 => 6.0,
        3 => 10.0,
        5 => 15.0,
        10 => 25.0,
        20 => 50.0,
    ];

    /** 2× apenas para 20 (3 colunas) */
    private const PAY_2_20 = 1.0;

    /**
     * Modo "roleta": sorteia um valor por coluna (linha do meio).
     * Quando $easyMode estiver ativo, aumenta a chance de sair uma combinação (valores iguais).
     * @param int $columns 3, 4 ou 5
     * @param bool $easyMode
     * @return array{reels: array<int, array<int, int>>, prize: int}
     */
    public function spinRoulette(int $columns = 3, bool $easyMode = false): array
    {
        $columns = max(3, min(5, (int) $columns));
        $target = random_int(1, 20);
        $forceCombo = $easyMode && random_int(1, 100) <= 50;
        $reels = [];
        for ($c = 0; $c < $columns; $c++) {
            $top = random_int(1, 20);
            $mid = $forceCombo ? $target : random_int(1, 20);
            $bottom = random_int(1, 20);
            $reels[$c] = [$top, $mid, $bottom];
        }
        return [
            'reels' => $reels,
            'prize' => $target, // apenas informativo
        ];
    }

    /**
     * Gera rolos (colunas × 3 linhas) e calcula ganho.
     *
     * @param float $bet Aposta em R$
     * @param int $columns 3, 4 ou 5
     * @return array{reels: array<int, array<int, int>>, win: float, multiplier: float}
     */
    public function spin(float $bet, int $columns = 3): array
    {
        $columns = max(3, min(5, (int) $columns));
        $reels = [];
        for ($c = 0; $c < $columns; $c++) {
            $reels[$c] = [
                $this->pickValue(),
                $this->pickValue(),
                $this->pickValue(),
            ];
        }
        $payline = [];
        foreach ($reels as $col) {
            $payline[] = $col[1];
        }
        $win = $this->computeWin($payline, $bet, $columns);
        // Multiplicador baseado no ganho dividido pela aposta
        $multiplier = $bet > 0 ? $win / $bet : 0.0;

        return [
            'reels' => $reels,
            'win' => round($win, 2),
            'multiplier' => round($multiplier, 2),
        ];
    }

    private function pickValue(): int
    {
        $total = 0;
        foreach (self::WEIGHTS as $w) {
            $total += $w;
        }
        $r = random_int(1, (int) $total);
        foreach (self::WEIGHTS as $val => $w) {
            $r -= $w;
            if ($r <= 0) {
                return $val;
            }
        }
        return 1;
    }

    private function computeWin(array $payline, float $bet, int $columns): float
    {
        $n = count($payline);
        if ($n < 3) {
            return 0.0;
        }

        $counts = array_count_values($payline);
        arsort($counts, SORT_NUMERIC);
        $maxVal = (int) array_key_first($counts);
        $maxCount = $counts[$maxVal];

        // 5 iguais (apenas quando colunas = 5)
        if ($columns === 5 && $maxCount >= 5 && isset(self::PAY_5[$maxVal])) {
            return $bet * (float) self::PAY_5[$maxVal];
        }
        // 4 iguais (quando colunas >= 4)
        if ($columns >= 4 && $maxCount >= 4 && isset(self::PAY_4[$maxVal])) {
            return $bet * (float) self::PAY_4[$maxVal];
        }
        // 3 iguais (sempre)
        if ($maxCount >= 3 && isset(self::PAY_3[$maxVal])) {
            return $bet * (float) self::PAY_3[$maxVal];
        }
        // Bónus para 3 colunas: 2 símbolos de R$ 20,00 na payline
        if ($maxCount === 2 && $n === 3 && $maxVal === 20) {
            return $bet * (float) self::PAY_2_20;
        }
        return 0.0;
    }

    /** Multiplicadores para exibir na paytable (3/4/5 colunas). */
    public static function getPaytable(): array
    {
        return [
            'values' => self::VALUES,
            'pay_3' => self::PAY_3,
            'pay_4' => self::PAY_4,
            'pay_5' => self::PAY_5,
            'pay_2_20' => self::PAY_2_20,
        ];
    }
}
