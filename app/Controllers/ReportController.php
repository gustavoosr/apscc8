<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\ActivityLog;
use App\Helpers\OrgScope;
use App\Helpers\PeriodFilter;
use App\Services\IndicatorService;

/** Relatórios em JSON (padrão) ou CSV (?format=csv). PDF: página imprimível na interface. */
final class ReportController extends BaseController
{
    public function consumption(Request $r): void
    {
        $f = PeriodFilter::from($r);
        $type = $r->q('type', 'water');
        $rows = [];
        foreach (($type === 'both' ? ['water', 'energy'] : [$type === 'energy' ? 'energy' : 'water']) as $t) {
            [$table, $col, $unit] = $t === 'water' ? ['water_consumption', 'quantity_m3', 'm3'] : ['energy_consumption', 'quantity_kwh', 'kWh'];
            $params = [];
            $where = [OrgScope::in('t.organization_id', $f['org_ids'], $params), 't.deleted_at IS NULL'];
            if ($f['sector_id']) { $where[] = 't.sector_id = ?'; $params[] = $f['sector_id']; }
            if ($f['from']) { $where[] = 't.reference_month >= ?'; $params[] = $f['from']; }
            if ($f['to']) { $where[] = 't.reference_month <= ?'; $params[] = $f['to']; }
            $st = Database::pdo()->prepare("SELECT o.name AS organization, s.name AS sector, DATE_FORMAT(t.reference_month,'%Y-%m') AS month, t.$col AS quantity FROM $table t JOIN sectors s ON s.id = t.sector_id JOIN organizations o ON o.id = t.organization_id WHERE " . implode(' AND ', $where) . ' ORDER BY t.reference_month, o.name, s.name');
            $st->execute($params);
            foreach ($st->fetchAll() as $row) {
                $rows[] = ['Recurso' => $t === 'water' ? 'Água' : 'Energia', 'Organização' => $row['organization'], 'Setor' => $row['sector'], 'Mês' => $row['month'], 'Quantidade' => (float) $row['quantity'], 'Unidade' => $unit];
            }
        }
        $this->output($r, 'consumo', $rows);
    }

    public function waste(Request $r): void
    {
        $f = PeriodFilter::from($r);
        $params = [];
        $where = [OrgScope::in('t.organization_id', $f['org_ids'], $params), 't.deleted_at IS NULL'];
        if ($f['sector_id']) { $where[] = 't.sector_id = ?'; $params[] = $f['sector_id']; }
        if ($f['from']) { $where[] = 't.record_date >= ?'; $params[] = $f['from']; }
        if ($f['to']) { $where[] = 't.record_date <= ?'; $params[] = $f['to']; }
        $st = Database::pdo()->prepare('SELECT t.record_date, o.name AS organization, s.name AS sector, c.name AS category, wt.name AS type, d.name AS destination, t.quantity_kg FROM waste_records t JOIN organizations o ON o.id = t.organization_id JOIN sectors s ON s.id = t.sector_id JOIN waste_types wt ON wt.id = t.waste_type_id JOIN waste_categories c ON c.id = wt.category_id JOIN waste_destinations d ON d.id = t.destination_id WHERE ' . implode(' AND ', $where) . ' ORDER BY t.record_date, o.name');
        $st->execute($params);
        $rows = array_map(fn ($x) => ['Data' => $x['record_date'], 'Organização' => $x['organization'], 'Setor' => $x['sector'], 'Categoria' => $x['category'], 'Tipo' => $x['type'], 'Destinação' => $x['destination'], 'Quantidade (kg)' => (float) $x['quantity_kg']], $st->fetchAll());
        $this->output($r, 'residuos', $rows);
    }

    public function goals(Request $r): void
    {
        $g = (new IndicatorService())->goals(PeriodFilter::from($r))['goals'];
        $status = ['ATINGIDA' => 'Atingida', 'EM_ANDAMENTO' => 'Em andamento', 'ATRASADA' => 'Atrasada'];
        $metric = ['WATER_M3' => 'Água (m³/mês)', 'ENERGY_KWH' => 'Energia (kWh/mês)', 'WASTE_KG' => 'Resíduos (kg/mês)', 'RECYCLING_RATE' => 'Taxa de reciclagem (%)'];
        $rows = array_map(fn ($x) => [
            'Objetivo' => $x['objective_title'], 'Meta' => $x['title'], 'Indicador' => $metric[$x['metric']],
            'Direção' => $x['direction'] === 'DECREASE' ? 'Redução' : 'Aumento',
            'Referência' => $x['baseline_value'], 'Desejado' => $x['target_value'], 'Atual' => $x['current_value'],
            'Progresso (%)' => $x['progress_pct'], 'Prazo' => $x['due_date'], 'Situação' => $status[$x['status']],
        ], $g);
        $this->output($r, 'metas', $rows);
    }

    private function output(Request $r, string $name, array $rows): void
    {
        ActivityLog::record($r, 'REPORT', 'reports');
        if ($r->q('format') === 'csv') {
            $this->csv($name, $rows);
        }
        Response::success($rows, 'Relatório gerado.', 200, ['total' => count($rows)]);
    }

    private function csv(string $name, array $rows): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ecomanager-' . $name . '-' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para o Excel reconhecer UTF-8
        if ($rows) {
            fputcsv($out, array_keys($rows[0]), ';', '"', '\\');
        }
        foreach ($rows as $row) {
            // Neutraliza injeção de fórmulas em planilhas.
            $row = array_map(fn ($v) => is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r") ? "'" . $v : $v, $row);
            fputcsv($out, array_map(fn ($v) => is_float($v) ? str_replace('.', ',', (string) $v) : $v, $row), ';', '"', '\\');
        }
        fclose($out);
        exit;
    }
}
