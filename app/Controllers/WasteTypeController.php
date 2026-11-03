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
use App\Validators\Validator;

/** Tipos e categorias de resíduos (dados globais; escrita restrita a GESTOR/ADMINISTRADOR nas rotas). */
final class WasteTypeController extends BaseController
{
    public function index(Request $r): void
    {
        $where = ['1=1'];
        $params = [];
        if ($c = $r->q('category_id')) { $where[] = 't.category_id = ?'; $params[] = (int) $c; }
        if ($r->q('active') !== null) { $where[] = 't.active = ?'; $params[] = filter_var($r->q('active'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0; }
        $st = Database::pdo()->prepare('SELECT t.id, t.category_id, c.name AS category, t.name, t.hazardous, t.active FROM waste_types t JOIN waste_categories c ON c.id = t.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY c.name, t.name');
        $st->execute($params);
        Response::success($st->fetchAll(), 'Tipos de resíduos listados.');
    }

    public function show(Request $r): void
    {
        Response::success($this->find($this->id($r)));
    }

    public function store(Request $r): void
    {
        $d = Validator::validate($r->body(), ['category_id' => 'required|int|min:1', 'name' => 'required|string|min:2|max:120', 'hazardous' => 'bool']);
        $this->assertCategory($d['category_id']);
        $pdo = Database::pdo();
        try {
            $pdo->prepare('INSERT INTO waste_types (category_id, name, hazardous) VALUES (?,?,?)')->execute([$d['category_id'], $d['name'], $d['hazardous'] ?? 0]);
        } catch (\PDOException $e) {
            $this->duplicate($e);
        }
        $id = (int) $pdo->lastInsertId();
        ActivityLog::record($r, 'CREATE', 'waste_types', $id);
        Response::created(['id' => $id], 'Tipo de resíduo cadastrado com sucesso.');
    }

    public function update(Request $r): void
    {
        $t = $this->find($this->id($r));
        $d = Validator::validate($r->body(), ['category_id' => 'required|int|min:1', 'name' => 'required|string|min:2|max:120', 'hazardous' => 'bool', 'active' => 'bool'], true);
        if (isset($d['category_id'])) { $this->assertCategory($d['category_id']); }
        if ($d) {
            try {
                $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($d)));
                Database::pdo()->prepare("UPDATE waste_types SET $sets WHERE id = ?")->execute([...array_values($d), $t['id']]);
            } catch (\PDOException $e) {
                $this->duplicate($e);
            }
        }
        ActivityLog::record($r, 'UPDATE', 'waste_types', (int) $t['id']);
        Response::success(['id' => (int) $t['id']], 'Tipo de resíduo atualizado com sucesso.');
    }

    public function destroy(Request $r): void
    {
        $t = $this->find($this->id($r));
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM waste_records WHERE waste_type_id = ?');
        $st->execute([$t['id']]);
        if ((int) $st->fetchColumn()) {
            throw new HttpException('Tipo em uso por registros. Desative-o em vez de excluir.', 409);
        }
        Database::pdo()->prepare('DELETE FROM waste_types WHERE id = ?')->execute([$t['id']]);
        ActivityLog::record($r, 'DELETE', 'waste_types', (int) $t['id']);
        Response::noContent();
    }

    // ---- categorias
    public function categories(Request $r): void
    {
        Response::success(Database::pdo()->query('SELECT id, name FROM waste_categories ORDER BY name')->fetchAll(), 'Categorias listadas.');
    }

    public function storeCategory(Request $r): void
    {
        $d = Validator::validate($r->body(), ['name' => 'required|string|min:2|max:100']);
        $pdo = Database::pdo();
        try {
            $pdo->prepare('INSERT INTO waste_categories (name) VALUES (?)')->execute([$d['name']]);
        } catch (\PDOException $e) {
            $this->duplicate($e);
        }
        $id = (int) $pdo->lastInsertId();
        ActivityLog::record($r, 'CREATE', 'waste_categories', $id);
        Response::created(['id' => $id], 'Categoria cadastrada com sucesso.');
    }

    private function find(int $id): array
    {
        $st = Database::pdo()->prepare('SELECT t.id, t.category_id, c.name AS category, t.name, t.hazardous, t.active FROM waste_types t JOIN waste_categories c ON c.id = t.category_id WHERE t.id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: throw new NotFoundException('Tipo de resíduo não encontrado.');
    }

    private function assertCategory(int $id): void
    {
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM waste_categories WHERE id = ?');
        $st->execute([$id]);
        if (!(int) $st->fetchColumn()) {
            throw new ValidationException([['field' => 'category_id', 'message' => 'Categoria inexistente.']]);
        }
    }

    private function duplicate(\PDOException $e): never
    {
        if (($e->errorInfo[1] ?? 0) === 1062) {
            throw new ValidationException([['field' => 'name', 'message' => 'Já existe um registro com este nome.']]);
        }
        throw $e;
    }
}
