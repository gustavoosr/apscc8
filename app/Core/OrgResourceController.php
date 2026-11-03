<?php
declare(strict_types=1);

namespace App\Core;

use App\Helpers\ActivityLog;
use App\Helpers\OrgScope;
use App\Helpers\Pagination;
use App\Validators\Validator;

/**
 * CRUD genérico para tabelas com organization_id. Aplica escopo de organização,
 * permissões por perfil, validação, paginação, exclusão lógica e auditoria.
 *
 * Regras de perfil:
 *  - OPERADOR: cria e consulta; edita apenas registros criados por ele; não exclui.
 *  - GESTOR/ADMINISTRADOR: criam, editam e excluem registros das suas organizações.
 */
abstract class OrgResourceController extends BaseController
{
    protected string $table;          // tabela principal (alias t)
    protected string $entityName;     // nome para auditoria
    protected string $label = 'Registro';
    protected string $columns = 't.*';
    protected string $joins = '';
    protected array $sortable = ['id' => 't.id'];
    protected string $defaultSort = '-id';
    protected bool $hasCreatedBy = true;
    protected bool $softDelete = true;
    protected bool $managerOnlyWrite = false; // true: operador não escreve

    /** Regras de validação campo => regra (inclui organization_id na criação). */
    abstract protected function rules(): array;

    /** Filtros adicionais a partir da query string. */
    protected function filters(Request $r, array &$where, array &$params): void {}

    /** Normaliza/valida regras de negócio; retorna colunas a persistir. */
    protected function prepare(array $data, ?array $existing, Request $r): array
    {
        return $data;
    }

    /** Permite enriquecer as linhas retornadas em listagens. */
    protected function transform(array $rows): array
    {
        return $rows;
    }

    protected function transformOne(array $row): array
    {
        return $row;
    }

    protected function afterSave(int $id, Request $r, bool $creating): void {}

    // ---------------------------------------------------------------- READ

    public function index(Request $r): void
    {
        $p = Pagination::from($r, $this->sortable, $this->defaultSort);
        $params = [];
        $where = [OrgScope::in('t.organization_id', OrgScope::ids($r), $params)];
        if ($this->softDelete) {
            $where[] = 't.deleted_at IS NULL';
        }
        $this->filters($r, $where, $params);
        $w = implode(' AND ', $where);

        $pdo = Database::pdo();
        $c = $pdo->prepare("SELECT COUNT(*) FROM {$this->table} t {$this->joins} WHERE $w");
        $c->execute($params);
        $st = $pdo->prepare("SELECT {$this->columns} FROM {$this->table} t {$this->joins} WHERE $w ORDER BY {$p->orderBy} {$p->limitSql()}");
        $st->execute($params);
        Response::success($this->transform($st->fetchAll()), $this->label . 's listados.', 200, $p->meta((int) $c->fetchColumn()));
    }

    public function show(Request $r): void
    {
        $row = $this->findScoped($this->id($r), $r);
        Response::success($this->transformOne($row));
    }

    // ---------------------------------------------------------------- WRITE

    public function store(Request $r): void
    {
        $this->assertCanWrite($r);
        $rules = array_merge(['organization_id' => 'required|int|min:1'], $this->rules());
        $data = Validator::validate($r->body(), $rules);
        OrgScope::assert($r, $data['organization_id']);

        $cols = $this->prepare($data, null, $r);
        $cols['organization_id'] = $data['organization_id'];
        if ($this->hasCreatedBy) {
            $cols['created_by'] = $r->user['id'];
        }
        $names = array_keys($cols);
        $sql = "INSERT INTO {$this->table} (" . implode(',', $names) . ') VALUES (' . implode(',', array_fill(0, count($names), '?')) . ')';
        $pdo = Database::pdo();
        $pdo->prepare($sql)->execute(array_values($cols));
        $id = (int) $pdo->lastInsertId();
        $this->afterSave($id, $r, true);
        ActivityLog::record($r, 'CREATE', $this->entityName, $id);
        Response::created(['id' => $id], $this->label . ' cadastrado com sucesso.');
    }

    public function update(Request $r): void
    {
        $this->assertCanWrite($r);
        $existing = $this->findScoped($this->id($r), $r, raw: true);
        if ($r->user['role'] === 'OPERADOR' && $this->hasCreatedBy && (int) $existing['created_by'] !== $r->user['id']) {
            throw new ForbiddenException('Operadores só podem editar registros criados por eles.');
        }
        $data = Validator::validate($r->body(), $this->rules(), true);
        $cols = $this->prepare($data, $existing, $r);
        unset($cols['organization_id'], $cols['created_by']);
        if ($cols) {
            $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($cols)));
            Database::pdo()->prepare("UPDATE {$this->table} SET $sets WHERE id = ?")->execute([...array_values($cols), $existing['id']]);
        }
        $this->afterSave((int) $existing['id'], $r, false);
        ActivityLog::record($r, 'UPDATE', $this->entityName, (int) $existing['id']);
        Response::success(['id' => (int) $existing['id']], $this->label . ' atualizado com sucesso.');
    }

    public function destroy(Request $r): void
    {
        if ($r->user['role'] === 'OPERADOR') {
            throw new ForbiddenException('Operadores não podem excluir registros.');
        }
        $existing = $this->findScoped($this->id($r), $r, raw: true);
        $this->beforeDelete($existing);
        $sql = $this->softDelete
            ? "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ?"
            : "DELETE FROM {$this->table} WHERE id = ?";
        Database::pdo()->prepare($sql)->execute([$existing['id']]);
        ActivityLog::record($r, 'DELETE', $this->entityName, (int) $existing['id']);
        Response::noContent();
    }

    protected function beforeDelete(array $existing): void {}

    // -------------------------------------------------------------- HELPERS

    protected function assertCanWrite(Request $r): void
    {
        if ($this->managerOnlyWrite && $r->user['role'] === 'OPERADOR') {
            throw new ForbiddenException();
        }
    }

    protected function findScoped(int $id, Request $r, bool $raw = false): array
    {
        $cols = $raw ? 't.*' : $this->columns;
        $deleted = $this->softDelete ? ' AND t.deleted_at IS NULL' : '';
        $st = Database::pdo()->prepare("SELECT $cols FROM {$this->table} t {$this->joins} WHERE t.id = ?$deleted");
        $st->execute([$id]);
        $row = $st->fetch();
        // 404 também para registros de outras organizações (não revela existência).
        if (!$row || !in_array((int) $row['organization_id'], $r->user['org_ids'] ?? [], true)) {
            throw new NotFoundException($this->label . ' não encontrado.');
        }
        return $row;
    }

    /** Garante que o setor pertence à organização informada. */
    protected function assertSector(int $sectorId, int $orgId): void
    {
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM sectors WHERE id = ? AND organization_id = ? AND deleted_at IS NULL AND active = 1');
        $st->execute([$sectorId, $orgId]);
        if (!(int) $st->fetchColumn()) {
            throw new ValidationException([['field' => 'sector_id', 'message' => 'Setor inválido para esta organização.']]);
        }
    }
}
