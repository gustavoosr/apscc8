<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class EnergyConsumptionController extends ConsumptionController
{
    protected string $table = 'energy_consumption';
    protected string $entityName = 'energy_consumption';
    protected string $label = 'Registro de energia';
    protected string $valueColumn = 'quantity_kwh';
    protected string $columns = 't.id, t.organization_id, t.sector_id, s.name AS sector_name, t.reference_month, t.quantity_kwh, t.notes, t.created_by, t.created_at';
    protected array $sortable = ['id' => 't.id', 'reference_month' => 't.reference_month', 'quantity_kwh' => 't.quantity_kwh', 'sector' => 's.name'];

    protected function rules(): array
    {
        return [
            'sector_id' => 'required|int|min:1',
            'reference_month' => 'required|month',
            'quantity_kwh' => 'required|numeric|min:0|max:999999999',
            'notes' => 'string|max:500',
        ];
    }

    protected function prepare(array $data, ?array $existing, Request $r): array
    {
        $orgId = (int) ($existing['organization_id'] ?? $data['organization_id']);
        if (isset($data['sector_id'])) {
            $this->assertSector((int) $data['sector_id'], $orgId);
        }
        return array_intersect_key($data, array_flip(['sector_id', 'reference_month', 'quantity_kwh', 'notes']));
    }
}
