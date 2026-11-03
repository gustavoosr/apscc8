<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\ForbiddenException;
use App\Core\HttpException;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use App\Helpers\ActivityLog;
use App\Helpers\OrgScope;
use App\Helpers\Pagination;
use App\Validators\Validator;

/** Organizações: todos leem as suas; apenas ADMINISTRADOR cria/exclui; GESTOR edita a sua. */
final class OrganizationController extends BaseController
{
    private const RULES = [
        'name' => 'required|string|min:2|max:150',
        'legal_id' => 'string|max:30',
        'email' => 'email|max:150',
        'phone' => 'string|max:30',
        'active' => 'bool',
    ];

    public function index(Request $r): void
    {
        $p = Pagination::from($r, ['id' => 'o.id', 'name' => 'o.name', 'created_at' => 'o.created_at'], 'name');
        $params = [];
        $where = [OrgScope::in('o.id', $r->user['org_ids'] ?: [0], $params), 'o.deleted_at IS NULL'];
        if ($s = $r->q('search')) {
            $where[] = '(o.name LIKE ? OR o.legal_id LIKE ?)';
            array_push($params, "%$s%", "%$s%");
        }
        $w = implode(' AND ', $where);
        $pdo = Database::pdo();
        $c = $pdo->prepare("SELECT COUNT(*) FROM organizations o WHERE $w");
        $c->execute($params);
        $st = $pdo->prepare("SELECT o.id, o.name, o.legal_id, o.email, o.phone, o.active, o.created_at FROM organizations o WHERE $w ORDER BY {$p->orderBy} {$p->limitSql()}");
        $st->execute($params);
        Response::success($st->fetchAll(), 'Organizações listadas.', 200, $p->meta((int) $c->fetchColumn()));
    }

    public function show(Request $r): void
    {
        Response::success($this->find($this->id($r), $r));
    }

    public function store(Request $r): void
    {
        $d = Validator::validate($r->body(), self::RULES);
        $this->assertUniqueLegalId($d['legal_id'] ?? null, 0);
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO organizations (name, legal_id, email, phone, active) VALUES (?,?,?,?,?)')
            ->execute([$d['name'], $d['legal_id'] ?? null, $d['email'] ?? null, $d['phone'] ?? null, $d['active'] ?? 1]);
        $id = (int) $pdo->lastInsertId();
        // O criador passa a ter acesso à organização.
        $pdo->prepare('INSERT IGNORE INTO organization_users (organization_id, user_id) VALUES (?,?)')->execute([$id, $r->user['id']]);
        ActivityLog::record($r, 'CREATE', 'organizations', $id);
        Response::created(['id' => $id], 'Organização cadastrada com sucesso.');
    }

    public function update(Request $r): void
    {
        $org = $this->find($this->id($r), $r);
        if ($r->user['role'] === 'OPERADOR') {
            throw new ForbiddenException();
        }
        $d = Validator::validate($r->body(), self::RULES, true);
        if (array_key_exists('legal_id', $d)) {
            $this->assertUniqueLegalId($d['legal_id'], (int) $org['id']);
        }
        if ($d) {
            $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($d)));
            Database::pdo()->prepare("UPDATE organizations SET $sets WHERE id = ?")->execute([...array_values($d), $org['id']]);
        }
        ActivityLog::record($r, 'UPDATE', 'organizations', (int) $org['id']);
        Response::success(['id' => (int) $org['id']], 'Organização atualizada com sucesso.');
    }

    public function destroy(Request $r): void
    {
        $org = $this->find($this->id($r), $r);
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT COUNT(*) FROM sectors WHERE organization_id = ? AND deleted_at IS NULL');
        $st->execute([$org['id']]);
        if ((int) $st->fetchColumn()) {
            throw new HttpException('A organização possui setores. Desative-a em vez de excluir.', 409);
        }
        $pdo->prepare('UPDATE organizations SET deleted_at = NOW(), active = 0, legal_id = NULL WHERE id = ?')->execute([$org['id']]);
        ActivityLog::record($r, 'DELETE', 'organizations', (int) $org['id']);
        Response::noContent();
    }

    private function find(int $id, Request $r): array
    {
        if (!in_array($id, $r->user['org_ids'] ?? [], true)) {
            throw new NotFoundException('Organização não encontrada.');
        }
        $st = Database::pdo()->prepare('SELECT id, name, legal_id, email, phone, active, created_at FROM organizations WHERE id = ? AND deleted_at IS NULL');
        $st->execute([$id]);
        return $st->fetch() ?: throw new NotFoundException('Organização não encontrada.');
    }

    private function assertUniqueLegalId(?string $legal, int $ignore): void
    {
        if (!$legal) {
            return;
        }
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM organizations WHERE legal_id = ? AND id <> ?');
        $st->execute([$legal, $ignore]);
        if ((int) $st->fetchColumn()) {
            throw new ValidationException([['field' => 'legal_id', 'message' => 'Identificação já cadastrada.']]);
        }
    }
}
