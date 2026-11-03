<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use App\Helpers\ActivityLog;
use App\Helpers\Pagination;
use App\Repositories\UserRepository;
use App\Validators\Validator;

/** Gerenciamento de usuários (somente ADMINISTRADOR, aplicado nas rotas). */
final class UserController extends BaseController
{
    private const ROLES = 'in:ADMINISTRADOR,GESTOR,OPERADOR';

    public function index(Request $r): void
    {
        $p = Pagination::from($r, ['name' => 'u.name', 'email' => 'u.email', 'role' => 'u.role', 'created_at' => 'u.created_at'], 'name');
        [$rows, $total] = (new UserRepository())->paginate($p, $r->q('search'), $r->q('role'));
        Response::success($rows, 'Usuários listados.', 200, $p->meta($total));
    }

    public function show(Request $r): void
    {
        Response::success($this->findOrFail($this->id($r)));
    }

    public function store(Request $r): void
    {
        $repo = new UserRepository();
        $d = Validator::validate($r->body(), [
            'name' => 'required|string|min:3|max:120',
            'email' => 'required|email|max:150',
            'password' => 'required|string|min:8|max:200',
            'role' => 'required|string|' . self::ROLES,
            'active' => 'bool',
        ]);
        if ($repo->emailExists($d['email'])) {
            throw new ValidationException([['field' => 'email', 'message' => 'E-mail já cadastrado.']]);
        }
        $orgIds = $this->orgIdsFrom($r);
        $id = $repo->create($d);
        $repo->syncOrganizations($id, $orgIds);
        ActivityLog::record($r, 'CREATE', 'users', $id);
        Response::created(['id' => $id], 'Usuário cadastrado com sucesso.');
    }

    public function update(Request $r): void
    {
        $id = $this->id($r);
        $this->findOrFail($id);
        $repo = new UserRepository();
        $d = Validator::validate($r->body(), [
            'name' => 'required|string|min:3|max:120',
            'email' => 'required|email|max:150',
            'password' => 'string|min:8|max:200',
            'role' => 'required|string|' . self::ROLES,
            'active' => 'bool',
        ], true);
        if (isset($d['email']) && $repo->emailExists($d['email'], $id)) {
            throw new ValidationException([['field' => 'email', 'message' => 'E-mail já cadastrado.']]);
        }
        if ($id === $r->user['id'] && ((isset($d['role']) && $d['role'] !== 'ADMINISTRADOR') || (isset($d['active']) && !$d['active']))) {
            throw new HttpException('Você não pode remover seu próprio acesso administrativo.', 422);
        }
        $repo->update($id, $d);
        if (array_key_exists('organization_ids', $r->body())) {
            $repo->syncOrganizations($id, $this->orgIdsFrom($r));
        }
        ActivityLog::record($r, 'UPDATE', 'users', $id);
        Response::success(['id' => $id], 'Usuário atualizado com sucesso.');
    }

    public function destroy(Request $r): void
    {
        $id = $this->id($r);
        $this->findOrFail($id);
        if ($id === $r->user['id']) {
            throw new HttpException('Você não pode excluir seu próprio usuário.', 422);
        }
        (new UserRepository())->softDelete($id);
        ActivityLog::record($r, 'DELETE', 'users', $id);
        Response::noContent();
    }

    private function findOrFail(int $id): array
    {
        return (new UserRepository())->find($id) ?? throw new NotFoundException('Usuário não encontrado.');
    }

    /** Valida a lista de organizações informada (devem existir). */
    private function orgIdsFrom(Request $r): array
    {
        $ids = $r->input('organization_ids', []);
        if (!is_array($ids)) {
            throw new ValidationException([['field' => 'organization_ids', 'message' => 'Deve ser uma lista.']]);
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $st = Database::pdo()->prepare("SELECT COUNT(*) FROM organizations WHERE deleted_at IS NULL AND id IN ($in)");
            $st->execute($ids);
            if ((int) $st->fetchColumn() !== count($ids)) {
                throw new ValidationException([['field' => 'organization_ids', 'message' => 'Organização inexistente.']]);
            }
        }
        return $ids;
    }
}
