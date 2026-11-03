<?php
declare(strict_types=1);

namespace App\Helpers;

/** Cálculos puros (sem acesso a banco), tratando divisão por zero e ausência de dados. */
final class Calc
{
    /** Variação percentual de $previous para $current; null se não houver base de comparação. */
    public static function variation(?float $current, ?float $previous): ?float
    {
        if ($current === null || $previous === null || $previous == 0.0) {
            return null;
        }
        return round(($current - $previous) / $previous * 100, 2);
    }

    /** Percentual part/total; null quando total é zero. */
    public static function percent(float $part, float $total): ?float
    {
        return $total > 0 ? round($part / $total * 100, 2) : null;
    }

    /**
     * Progresso da meta, respeitando a direção.
     * DECREASE: (base - atual) / (base - alvo); INCREASE: (atual - base) / (alvo - base).
     * Retorna valor bruto em % (pode ser <0 ou >100) ou null se base == alvo.
     */
    public static function goalProgress(string $direction, float $baseline, float $target, float $current): ?float
    {
        $den = $direction === 'DECREASE' ? $baseline - $target : $target - $baseline;
        if ($den == 0.0) {
            return null;
        }
        $num = $direction === 'DECREASE' ? $baseline - $current : $current - $baseline;
        return round($num / $den * 100, 2);
    }

    public static function goalStatus(?float $progress, string $dueDate, ?\DateTimeImmutable $today = null): string
    {
        $today ??= new \DateTimeImmutable('today');
        if ($progress !== null && $progress >= 100) {
            return 'ATINGIDA';
        }
        return $today > new \DateTimeImmutable($dueDate) ? 'ATRASADA' : 'EM_ANDAMENTO';
    }
}
