<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Request;
use App\Core\ValidationException;

/** Filtros comuns de indicadores: organização, setor e período (from/to). */
final class PeriodFilter
{
    public static function from(Request $r): array
    {
        $sector = $r->q('sector_id');
        return [
            'org_ids' => OrgScope::ids($r),
            'sector_id' => $sector !== null ? (int) $sector : null,
            'from' => self::date($r->q('from'), 'from', false),
            'to' => self::date($r->q('to'), 'to', true),
        ];
    }

    /** Aceita AAAA-MM-DD ou AAAA-MM (início/fim do mês conforme $end). */
    private static function date(?string $v, string $field, bool $end): ?string
    {
        if ($v === null) {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}$/', $v)) {
            $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $v . '-01');
            return ($d && $d->format('Y-m') === $v) ? ($end ? $d->format('Y-m-t') : $d->format('Y-m-d')) : self::fail($field);
        }
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $v);
        return ($d && $d->format('Y-m-d') === $v) ? $v : self::fail($field);
    }

    private static function fail(string $field): never
    {
        throw new ValidationException([['field' => $field, 'message' => 'Data inválida (use AAAA-MM-DD ou AAAA-MM).']]);
    }
}
