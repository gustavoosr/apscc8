<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Helpers\UnitConverter;

final class WaterConsumptionController extends ConsumptionController
{
    protected string $table = 'water_consumption';
    protected string $entityName = 'water_consumption';
    protected string $label = 'Registro de água';
    protected string $valueColumn = 'quantity_m3';
    protected string $columns = 't.id, t.organization_id, t.sector_id, s.name AS sector_name, t.reference_month, t.quantity_m3, t.original_quantity, t.original_unit, t.notes, t.created_by, t.created_at';
    protected array $sortable = ['id' => 't.id', 'reference_month' => 't.reference_month', 'quantity_m3' => 't.quantity_m3', 'sector' => 's.name'];

    protected function rules(): array
    {
        return [
            'sector_id' => 'required|int|min:1',
            'reference_month' => 'required|month',
            'quantity' => 'required|numeric|min:0|max:999999999',
            'unit' => 'required|string|in:m3,L',
            'notes' => 'string|max:500',
        ];
    }

    protected function prepare(array $data, ?array $existing, Request $r): array
    {
        $orgId = (int) ($existing['organization_id'] ?? $data['organization_id']);
        if (isset($data['sector_id'])) {
            $this->assertSector((int) $data['sector_id'], $orgId);
        }
        $cols = array_intersect_key($data, array_flip(['sector_id', 'reference_month', 'notes']));
        if (isset($data['quantity']) || isset($data['unit'])) {
            $q = (float) ($data['quantity'] ?? $existing['original_quantity']);
            $u = (string) ($data['unit'] ?? $existing['original_unit']);
            $cols['original_quantity'] = $q;
            $cols['original_unit'] = $u;
            $cols['quantity_m3'] = round(UnitConverter::waterToM3($q, $u), 3);
        }
        return $cols;
    }
}
