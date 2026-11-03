<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\OrgResourceController;
use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use App\Helpers\OrgScope;
use App\Helpers\UnitConverter;
use App\Services\IndicatorService;

final class WasteController extends OrgResourceController
{
    protected string $table = 'waste_records';
    protected string $entityName = 'waste_records';
    protected string $label = 'Registro de resíduo';
    protected string $columns = 't.id, t.organization_id, t.sector_id, s.name AS sector_name, t.waste_type_id, wt.name AS waste_type, wc.name AS category, t.destination_id, d.name AS destination, d.code AS destination_code, t.record_date, t.quantity_kg, t.notes, t.created_by, t.created_at';
    protected string $joins = 'JOIN sectors s ON s.id = t.sector_id JOIN waste_types wt ON wt.id = t.waste_type_id JOIN waste_categories wc ON wc.id = wt.category_id JOIN waste_destinations d ON d.id = t.destination_id';
    protected array $sortable = ['id' => 't.id', 'record_date' => 't.record_date', 'quantity_kg' => 't.quantity_kg', 'sector' => 's.name', 'type' => 'wt.name', 'destination' => 'd.name'];
    protected string $defaultSort = '-record_date';

    protected function rules(): array
    {
        return [
            'sector_id' => 'required|int|min:1',
            'waste_type_id' => 'required|int|min:1',
            'destination_id' => 'required|int|min:1',
            'record_date' => 'required|date',
            'quantity' => 'required|numeric|min:0|max:999999999',
            'unit' => 'required|string|in:kg,t',
            'notes' => 'string|max:500',
        ];
    }

    protected function filters(Request $r, array &$where, array &$params): void
    {
        foreach (['sector_id' => 't.sector_id', 'waste_type_id' => 't.waste_type_id', 'destination_id' => 't.destination_id'] as $q => $col) {
            if ($v = $r->q($q)) {
                $where[] = "$col = ?";
                $params[] = (int) $v;
            }
        }
        if ($v = $r->q('from')) { $where[] = 't.record_date >= ?'; $params[] = $v; }
        if ($v = $r->q('to')) { $where[] = 't.record_date <= ?'; $params[] = $v; }
    }

    protected function prepare(array $data, ?array $existing, Request $r): array
    {
        $orgId = (int) ($existing['organization_id'] ?? $data['organization_id']);
        if (isset($data['sector_id'])) {
            $this->assertSector((int) $data['sector_id'], $orgId);
        }
        $pdo = Database::pdo();
        foreach (['waste_type_id' => 'waste_types', 'destination_id' => 'waste_destinations'] as $f => $t) {
            if (isset($data[$f])) {
                $st = $pdo->prepare("SELECT COUNT(*) FROM $t WHERE id = ? AND active = 1");
                $st->execute([$data[$f]]);
                if (!(int) $st->fetchColumn()) {
                    throw new ValidationException([['field' => $f, 'message' => 'Valor inválido.']]);
                }
            }
        }
        $cols = array_intersect_key($data, array_flip(['sector_id', 'waste_type_id', 'destination_id', 'record_date', 'notes']));
        if (isset($data['quantity']) || isset($data['unit'])) {
            if (!isset($data['quantity'], $data['unit'])) {
                throw new ValidationException([['field' => 'quantity', 'message' => 'Informe quantidade e unidade juntas.']]);
            }
            $cols['quantity_kg'] = round(UnitConverter::wasteToKg((float) $data['quantity'], $data['unit']), 3);
        }
        return $cols;
    }

    /** GET /waste/summary: totais por destinação e taxa de reciclagem. */
    public function summary(Request $r): void
    {
        $f = ['org_ids' => OrgScope::ids($r), 'sector_id' => $r->q('sector_id'), 'from' => $r->q('from'), 'to' => $r->q('to')];
        Response::success((new IndicatorService())->waste($f));
    }
}
