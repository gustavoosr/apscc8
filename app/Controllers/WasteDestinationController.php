<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\ActivityLog;
use App\Validators\Validator;

/** Destinações de resíduos: leitura para todos; ativação/renomeação só por ADMINISTRADOR. */
final class WasteDestinationController extends BaseController
{
    public function index(Request $r): void
    {
        Response::success(Database::pdo()->query('SELECT id, code, name, active FROM waste_destinations ORDER BY id')->fetchAll(), 'Destinações listadas.');
    }

    public function show(Request $r): void
    {
        $st = Database::pdo()->prepare('SELECT id, code, name, active FROM waste_destinations WHERE id = ?');
        $st->execute([$this->id($r)]);
        Response::success($st->fetch() ?: throw new NotFoundException('Destinação não encontrada.'));
    }

    public function store(Request $r): void
    {
        $d = Validator::validate($r->body(), ['code' => 'required|string|min:2|max:30', 'name' => 'required|string|min:2|max:100']);
        $pdo = Database::pdo();
        $code = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', $d['code']));
        try {
            $pdo->prepare('INSERT INTO waste_destinations (code, name) VALUES (?,?)')->execute([$code, $d['name']]);
        } catch (\PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                throw new \App\Core\ValidationException([['field' => 'code', 'message' => 'Código já existe.']]);
            }
            throw $e;
        }
        $id = (int) $pdo->lastInsertId();
        ActivityLog::record($r, 'CREATE', 'waste_destinations', $id);
        Response::created(['id' => $id], 'Destinação cadastrada com sucesso.');
    }

    public function update(Request $r): void
    {
        $id = $this->id($r);
        $d = Validator::validate($r->body(), ['name' => 'required|string|min:2|max:100', 'active' => 'bool'], true);
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM waste_destinations WHERE id = ?');
        $st->execute([$id]);
        if (!(int) $st->fetchColumn()) {
            throw new NotFoundException('Destinação não encontrada.');
        }
        if ($d) {
            $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($d)));
            Database::pdo()->prepare("UPDATE waste_destinations SET $sets WHERE id = ?")->execute([...array_values($d), $id]);
        }
        ActivityLog::record($r, 'UPDATE', 'waste_destinations', $id);
        Response::success(['id' => $id], 'Destinação atualizada com sucesso.');
    }
}
