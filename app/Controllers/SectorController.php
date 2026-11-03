<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\OrgResourceController;
use App\Core\Request;
use App\Core\ValidationException;

final class SectorController extends OrgResourceController
{
    protected string $table = 'sectors';
    protected string $entityName = 'sectors';
    protected string $label = 'Setor';
    protected string $columns = 't.id, t.organization_id, t.name, t.description, t.active, t.created_at, o.name AS organization_name';
    protected string $joins = 'JOIN organizations o ON o.id = t.organization_id';
    protected array $sortable = ['id' => 't.id', 'name' => 't.name', 'created_at' => 't.created_at'];
    protected string $defaultSort = 'name';
    protected bool $hasCreatedBy = false;
    protected bool $managerOnlyWrite = true;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:120',
            'description' => 'string|max:255',
            'active' => 'bool',
        ];
    }

    protected function filters(Request $r, array &$where, array &$params): void
    {
        if (($a = $r->q('active')) !== null) {
            $where[] = 't.active = ?';
            $params[] = filter_var($a, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
        if ($s = $r->q('search')) {
            $where[] = 't.name LIKE ?';
            $params[] = "%$s%";
        }
    }

    protected function prepare(array $data, ?array $existing, Request $r): array
    {
        $orgId = $existing['organization_id'] ?? $data['organization_id'];
        if (isset($data['name'])) {
            $st = Database::pdo()->prepare('SELECT COUNT(*) FROM sectors WHERE organization_id = ? AND name = ? AND id <> ? AND deleted_at IS NULL');
            $st->execute([$orgId, $data['name'], $existing['id'] ?? 0]);
            if ((int) $st->fetchColumn()) {
                throw new ValidationException([['field' => 'name', 'message' => 'Já existe um setor com este nome na organização.']]);
            }
        }
        unset($data['organization_id']);
        return $data;
    }

    protected function beforeDelete(array $existing): void
    {
        $pdo = Database::pdo();
        foreach (['water_consumption', 'energy_consumption', 'waste_records'] as $t) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM $t WHERE sector_id = ? AND deleted_at IS NULL");
            $st->execute([$existing['id']]);
            if ((int) $st->fetchColumn()) {
                throw new HttpException('Este setor possui registros e não pode ser excluído. Desative-o em vez disso.', 409);
            }
        }
    }
}
