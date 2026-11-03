<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\OrgResourceController;
use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use App\Services\IndicatorService;

/** Metas ambientais. Leituras retornam a meta já avaliada (valor atual, progresso, status). */
final class GoalController extends OrgResourceController
{
    protected string $table = 'environmental_goals';
    protected string $entityName = 'environmental_goals';
    protected string $label = 'Meta';
    protected bool $hasCreatedBy = false;
    protected bool $managerOnlyWrite = true;
    protected string $columns = 't.*, o.title AS objective_title, s.name AS sector_name';
    protected string $joins = 'JOIN environmental_objectives o ON o.id = t.objective_id LEFT JOIN sectors s ON s.id = t.sector_id';
    protected array $sortable = ['id' => 't.id', 'due_date' => 't.due_date', 'title' => 't.title'];
    protected string $defaultSort = 'due_date';

    protected function rules(): array
    {
        return [
            'objective_id' => 'required|int|min:1',
            'sector_id' => 'int|min:1',
            'title' => 'required|string|min:3|max:150',
            'metric' => 'required|string|in:WATER_M3,ENERGY_KWH,WASTE_KG,RECYCLING_RATE',
            'direction' => 'required|string|in:DECREASE,INCREASE',
            'baseline_value' => 'required|numeric|min:0',
            'target_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'due_date' => 'required|date',
        ];
    }

    protected function filters(Request $r, array &$where, array &$params): void
    {
        if ($v = $r->q('objective_id')) { $where[] = 't.objective_id = ?'; $params[] = (int) $v; }
        if ($v = $r->q('metric')) { $where[] = 't.metric = ?'; $params[] = $v; }
    }

    protected function prepare(array $data, ?array $existing, Request $r): array
    {
        $m = array_merge($existing ?? [], $data);
        $orgId = (int) $m['organization_id'];
        if (isset($data['objective_id'])) {
            $st = Database::pdo()->prepare('SELECT COUNT(*) FROM environmental_objectives WHERE id = ? AND organization_id = ? AND deleted_at IS NULL');
            $st->execute([$data['objective_id'], $orgId]);
            if (!(int) $st->fetchColumn()) {
                throw new ValidationException([['field' => 'objective_id', 'message' => 'Objetivo inválido para esta organização.']]);
            }
        }
        if (!empty($data['sector_id'])) {
            $this->assertSector((int) $data['sector_id'], $orgId);
        }
        if ((float) $m['baseline_value'] === (float) $m['target_value']) {
            throw new ValidationException([['field' => 'target_value', 'message' => 'A meta deve ser diferente do valor de referência.']]);
        }
        if ($m['due_date'] < $m['start_date']) {
            throw new ValidationException([['field' => 'due_date', 'message' => 'O prazo deve ser posterior ao início.']]);
        }
        // A direção deve ser coerente com os valores: redução => alvo < referência.
        $ok = $m['direction'] === 'DECREASE' ? (float) $m['target_value'] < (float) $m['baseline_value'] : (float) $m['target_value'] > (float) $m['baseline_value'];
        if (!$ok) {
            throw new ValidationException([['field' => 'direction', 'message' => $m['direction'] === 'DECREASE'
                ? 'Em metas de redução, o valor desejado deve ser menor que o de referência.'
                : 'Em metas de aumento, o valor desejado deve ser maior que o de referência.']]);
        }
        if ($m['metric'] === 'RECYCLING_RATE' && (float) $m['target_value'] > 100) {
            throw new ValidationException([['field' => 'target_value', 'message' => 'Percentual não pode exceder 100.']]);
        }
        unset($data['organization_id']);
        if (array_key_exists('sector_id', $data) && $data['sector_id'] === null) {
            $data['sector_id'] = null;
        }
        // Ao criar, sector_id ausente deve ser gravado como NULL de forma explícita.
        if ($existing === null) {
            $data['sector_id'] ??= null;
        }
        return $data;
    }

    protected function transform(array $rows): array
    {
        $svc = new IndicatorService();
        return array_map(fn ($g) => $svc->evaluate($g), $rows);
    }

    protected function transformOne(array $row): array
    {
        return (new IndicatorService())->evaluate($row);
    }

    /** GET /environmental-goals/{id}/progress */
    public function progress(Request $r): void
    {
        $g = $this->findScoped($this->id($r), $r);
        $svc = new IndicatorService();
        Response::success(['goal' => $svc->evaluate($g), 'history' => $svc->goalHistory($g)]);
    }
}
