<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use App\Core\Request;

/** Trilha de auditoria. Registra apenas ação/entidade, nunca dados sensíveis. */
final class ActivityLog
{
    public static function record(?Request $r, string $action, string $entity, ?int $entityId = null, ?int $userId = null): void
    {
        $uid = $userId ?? ($r?->user['id'] ?? null);
        Database::pdo()
            ->prepare('INSERT INTO activity_logs (user_id, action, entity, entity_id, ip) VALUES (?,?,?,?,?)')
            ->execute([$uid, $action, $entity, $entityId, $r?->ip()]);
    }
}
