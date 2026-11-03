<?php
declare(strict_types=1);

namespace App\Helpers;

/** Conversão para unidades padrão: água em m³, resíduos em kg. */
final class UnitConverter
{
    public static function waterToM3(float $q, string $unit): float
    {
        return $unit === 'L' ? $q / 1000 : $q;
    }

    public static function wasteToKg(float $q, string $unit): float
    {
        return $unit === 't' ? $q * 1000 : $q;
    }
}
