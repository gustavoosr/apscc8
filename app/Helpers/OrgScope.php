<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\ForbiddenException;
use App\Core\Request;

/** Garante que o usuário só acesse organizações às quais está vinculado. */
final class OrgScope
{
    /** Lança 403 se o usuário não tem acesso à organização. */
    public static function assert(Request $r, int $orgId): void
    {
        if (!in_array($orgId, $r->user['org_ids'] ?? [], true)) {
            throw new ForbiddenException('Sem acesso a esta organização.');
        }
    }

    /**
     * Lista de organizações para filtro: parâmetro organization_id (se permitido)
     * ou todas as do usuário. Pode retornar [0] para garantir resultado vazio.
     */
    public static function ids(Request $r): array
    {
        $allowed = $r->user['org_ids'] ?? [];
        $req = $r->q('organization_id');
        if ($req !== null) {
            $req = (int) $req;
            if (!in_array($req, $allowed, true)) {
                throw new ForbiddenException('Sem acesso a esta organização.');
            }
            return [$req];
        }
        return $allowed ?: [0];
    }

    /** Gera "col IN (?,?,?)" e acrescenta os valores a $params. */
    public static function in(string $col, array $ids, array &$params): string
    {
        $params = array_merge($params, array_values($ids));
        return $col . ' IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
    }
}
