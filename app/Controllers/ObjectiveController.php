<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\OrgResourceController;
use App\Core\Request;

final class ObjectiveController extends OrgResourceController
{
    protected string $table = 'environmental_objectives';
    protected string $entityName = 'environmental_objectives';
    protected string $label = 'Objetivo';
    protected string $columns = 't.id, t.organization_id, t.title, t.description, t.status, t.created_at';
    protected array $sortable = ['id' => 't.id', 'title' => 't.title', 'created_at' => 't.created_at'];
    protected string $defaultSort = 'title';
    protected bool $managerOnlyWrite = true;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:150',
            'description' => 'string|max:500',
            'status' => 'string|in:ATIVO,ENCERRADO',
        ];
    }

    protected function filters(Request $r, array &$where, array &$params): void
    {
        if ($s = $r->q('status')) {
            $where[] = 't.status = ?';
            $params[] = $s;
        }
    }

    protected function prepare(array $data, ?array $existing, Request $r): array
    {
        unset($data['organization_id']);
        return $data;
    }

    protected function beforeDelete(array $existing): void
    {
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM environmental_goals WHERE objective_id = ? AND deleted_at IS NULL');
        $st->execute([$existing['id']]);
        if ((int) $st->fetchColumn()) {
            throw new HttpException('O objetivo possui metas. Exclua as metas primeiro ou encerre o objetivo.', 409);
        }
    }
}
