<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Calc;
use App\Helpers\OrgScope;

/**
 * Cálculo centralizado de indicadores ambientais. Todas as telas (dashboard,
 * indicadores, relatórios, metas) usam esta classe para garantir consistência.
 *
 * Filtros ($f): org_ids (array), sector_id (?int), from (Y-m-d ?), to (Y-m-d ?)
 */
final class IndicatorService
{
    private const MONTHLY = [
        'WATER_M3'   => ['water_consumption', 'quantity_m3', 'reference_month'],
        'ENERGY_KWH' => ['energy_consumption', 'quantity_kwh', 'reference_month'],
        'WASTE_KG'   => ['waste_records', 'quantity_kg', 'record_date'],
    ];

    /** Série mensal [Y-m-01 => valor] de uma métrica. */
    public function monthlySeries(string $metric, array $f): array
    {
        if ($metric === 'RECYCLING_RATE') {
            $total = $this->wasteMonthly($f, false);
            $rec = $this->wasteMonthly($f, true);
            $out = [];
            foreach ($total as $m => $t) {
                $pct = Calc::percent($rec[$m] ?? 0.0, $t);
                if ($pct !== null) {
                    $out[$m] = $pct;
                }
            }
            return $out;
        }
        [$table, $col, $dateCol] = self::MONTHLY[$metric];
        $params = [];
        $where = $this->where($f, $dateCol, $params);
        $monthExpr = $dateCol === 'reference_month' ? 'reference_month' : "DATE_FORMAT($dateCol,'%Y-%m-01')";
        $st = Database::pdo()->prepare("SELECT $monthExpr AS m, SUM($col) AS v FROM $table WHERE $where GROUP BY m ORDER BY m");
        $st->execute($params);
        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[$row['m']] = round((float) $row['v'], 3);
        }
        return $out;
    }

    private function wasteMonthly(array $f, bool $recycledOnly): array
    {
        $params = [];
        $where = $this->where($f, 'record_date', $params, 'w');
        if ($recycledOnly) {
            $where .= " AND d.code = 'RECICLAGEM'";
        }
        $st = Database::pdo()->prepare("SELECT DATE_FORMAT(w.record_date,'%Y-%m-01') AS m, SUM(w.quantity_kg) AS v FROM waste_records w JOIN waste_destinations d ON d.id = w.destination_id WHERE $where GROUP BY m");
        $st->execute($params);
        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[$row['m']] = (float) $row['v'];
        }
        return $out;
    }

    /** Indicadores de consumo (água ou energia). */
    public function consumption(string $metric, array $f): array
    {
        $series = $this->monthlySeries($metric, $f);
        $months = array_keys($series);
        $last = $months ? end($months) : null;
        $prev = count($months) > 1 ? $months[count($months) - 2] : null;
        // O mês anterior deve ser o mês calendário imediatamente anterior.
        if ($last && $prev && date('Y-m-01', strtotime("$last -1 month")) !== $prev) {
            $prev = null;
        }
        $current = $last ? $series[$last] : null;
        $previous = $prev ? $series[$prev] : null;
        $total = round(array_sum($series), 3);

        return [
            'total' => $total,
            'average_monthly' => $series ? round($total / count($series), 3) : null,
            'latest_month' => $last,
            'latest_month_total' => $current,
            'previous_month_total' => $previous,
            'variation_pct' => Calc::variation($current, $previous),
            'has_data' => (bool) $series,
            'history' => array_map(fn ($m, $v) => ['month' => substr($m, 0, 7), 'total' => $v], array_keys($series), $series),
            'by_sector' => $this->bySector($metric, $f),
        ];
    }

    private function bySector(string $metric, array $f): array
    {
        [$table, $col, $dateCol] = self::MONTHLY[$metric];
        $params = [];
        $where = $this->where($f, $dateCol, $params, 't');
        $st = Database::pdo()->prepare("SELECT s.id, s.name, SUM(t.$col) AS total FROM $table t JOIN sectors s ON s.id = t.sector_id WHERE $where GROUP BY s.id, s.name ORDER BY total DESC");
        $st->execute($params);
        return array_map(fn ($r) => ['sector_id' => (int) $r['id'], 'sector' => $r['name'], 'total' => round((float) $r['total'], 3)], $st->fetchAll());
    }

    /** Indicadores de resíduos. */
    public function waste(array $f): array
    {
        $params = [];
        $where = $this->where($f, 'record_date', $params, 'w');
        $st = Database::pdo()->prepare("SELECT d.code, d.name, COALESCE(SUM(w.quantity_kg),0) AS kg FROM waste_destinations d LEFT JOIN waste_records w ON w.destination_id = d.id AND $where GROUP BY d.id, d.code, d.name ORDER BY d.id");
        $st->execute($params);
        $rows = $st->fetchAll();
        $total = array_sum(array_map(fn ($r) => (float) $r['kg'], $rows));
        $byCode = [];
        $dist = [];
        foreach ($rows as $r) {
            $kg = round((float) $r['kg'], 3);
            $byCode[$r['code']] = $kg;
            $dist[] = ['code' => $r['code'], 'name' => $r['name'], 'kg' => $kg, 'pct' => Calc::percent($kg, $total)];
        }
        $recycled = $byCode['RECICLAGEM'] ?? 0.0;
        $reused = $byCode['REUTILIZACAO'] ?? 0.0;
        $composted = $byCode['COMPOSTAGEM'] ?? 0.0;
        $total = round($total, 3);

        $series = $this->monthlySeries('WASTE_KG', $f);
        return [
            'total_kg' => $total,
            'recycled_kg' => $recycled,
            'reused_kg' => $reused,
            'composted_kg' => $composted,
            'other_kg' => round($total - $recycled - $reused - $composted, 3),
            'recycling_pct' => Calc::percent($recycled, $total),
            'has_data' => $total > 0,
            'by_destination' => $dist,
            'history' => array_map(fn ($m, $v) => ['month' => substr($m, 0, 7), 'total' => $v], array_keys($series), $series),
        ];
    }

    // ----------------------------------------------------------------- METAS

    /** Avalia todas as metas (não excluídas) das organizações. */
    public function goals(array $f, ?int $objectiveId = null): array
    {
        $params = [];
        $where = [OrgScope::in('g.organization_id', $f['org_ids'], $params), 'g.deleted_at IS NULL'];
        if ($objectiveId) {
            $where[] = 'g.objective_id = ?';
            $params[] = $objectiveId;
        }
        if (!empty($f['sector_id'])) {
            $where[] = '(g.sector_id = ? OR g.sector_id IS NULL)';
            $params[] = (int) $f['sector_id'];
        }
        $st = Database::pdo()->prepare('SELECT g.*, o.title AS objective_title, s.name AS sector_name FROM environmental_goals g JOIN environmental_objectives o ON o.id = g.objective_id LEFT JOIN sectors s ON s.id = g.sector_id WHERE ' . implode(' AND ', $where) . ' ORDER BY g.due_date');
        $st->execute($params);
        $goals = array_map(fn ($g) => $this->evaluate($g), $st->fetchAll());

        $count = fn (string $s) => count(array_filter($goals, fn ($g) => $g['status'] === $s));
        $total = count($goals);
        $avg = $total ? round(array_sum(array_column($goals, 'progress_pct')) / $total, 2) : null;
        return [
            'total' => $total,
            'achieved' => $count('ATINGIDA'),
            'in_progress' => $count('EM_ANDAMENTO'),
            'late' => $count('ATRASADA'),
            'average_progress_pct' => $avg,
            'goals' => $goals,
        ];
    }

    /** Avalia uma meta: valor atual (último mês com dados dentro do período), progresso e status. */
    public function evaluate(array $g, ?\DateTimeImmutable $today = null): array
    {
        $today ??= new \DateTimeImmutable('today');
        $end = min($today->format('Y-m-d'), $g['due_date']);
        $series = $this->goalSeries($g, $end);
        $current = $series ? end($series) : null;

        $raw = $current === null ? null : Calc::goalProgress($g['direction'], (float) $g['baseline_value'], (float) $g['target_value'], $current);
        $status = Calc::goalStatus($raw, $g['due_date'], $today);

        return [
            'id' => (int) $g['id'],
            'objective_id' => (int) $g['objective_id'],
            'objective_title' => $g['objective_title'] ?? null,
            'organization_id' => (int) $g['organization_id'],
            'sector_id' => $g['sector_id'] !== null ? (int) $g['sector_id'] : null,
            'sector_name' => $g['sector_name'] ?? null,
            'title' => $g['title'],
            'metric' => $g['metric'],
            'direction' => $g['direction'],
            'baseline_value' => (float) $g['baseline_value'],
            'target_value' => (float) $g['target_value'],
            'start_date' => $g['start_date'],
            'due_date' => $g['due_date'],
            'current_value' => $current,
            'progress_raw_pct' => $raw,
            'progress_pct' => $raw === null ? 0.0 : max(0.0, min(100.0, $raw)),
            'status' => $status,
            'has_data' => $current !== null,
        ];
    }

    /** Série mensal da meta [Y-m-01 => valor] dentro do período da meta. */
    public function goalSeries(array $g, ?string $end = null): array
    {
        $f = [
            'org_ids' => [(int) $g['organization_id']],
            'sector_id' => $g['sector_id'] !== null ? (int) $g['sector_id'] : null,
            'from' => substr($g['start_date'], 0, 8) . '01',
            'to' => $end ?? $g['due_date'],
        ];
        return $this->monthlySeries($g['metric'], $f);
    }

    /** Evolução mensal do progresso médio das metas (sem gravar snapshots). */
    public function goalsEvolution(array $f): array
    {
        $acc = [];
        foreach ($this->goals($f)['goals'] as $ev) {
            $g = [
                'organization_id' => $ev['organization_id'], 'sector_id' => $ev['sector_id'], 'metric' => $ev['metric'],
                'start_date' => $ev['start_date'], 'due_date' => $ev['due_date'],
            ];
            foreach ($this->goalSeries($g) as $month => $value) {
                $raw = Calc::goalProgress($ev['direction'], $ev['baseline_value'], $ev['target_value'], $value);
                $acc[$month][] = $raw === null ? 0.0 : max(0.0, min(100.0, $raw));
            }
        }
        ksort($acc);
        $out = [];
        foreach ($acc as $m => $vals) {
            $out[] = ['month' => substr($m, 0, 7), 'average_progress_pct' => round(array_sum($vals) / count($vals), 2), 'goals' => count($vals)];
        }
        return $out;
    }

    /** Histórico mensal de progresso da meta (e atualiza os snapshots). */
    public function goalHistory(array $g): array
    {
        $out = [];
        $pdo = Database::pdo();
        $up = $pdo->prepare('INSERT INTO environmental_goal_progress (goal_id, reference_month, current_value, progress_pct) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE current_value = VALUES(current_value), progress_pct = VALUES(progress_pct)');
        foreach ($this->goalSeries($g) as $month => $value) {
            $raw = Calc::goalProgress($g['direction'], (float) $g['baseline_value'], (float) $g['target_value'], $value);
            $pct = $raw === null ? 0.0 : max(0.0, min(100.0, $raw));
            $up->execute([$g['id'], $month, $value, $pct]);
            $out[] = ['month' => substr($month, 0, 7), 'current_value' => $value, 'progress_pct' => $pct];
        }
        return $out;
    }

    // ------------------------------------------------------------- AUXILIAR

    /** Monta a cláusula WHERE comum; $alias prefixa as colunas quando informado. */
    private function where(array $f, string $dateCol, array &$params, string $alias = ''): string
    {
        $p = $alias ? $alias . '.' : '';
        $w = [OrgScope::in($p . 'organization_id', $f['org_ids'], $params), $p . 'deleted_at IS NULL'];
        if (!empty($f['sector_id'])) {
            $w[] = $p . 'sector_id = ?';
            $params[] = (int) $f['sector_id'];
        }
        if (!empty($f['from'])) {
            $w[] = "$p$dateCol >= ?";
            $params[] = $f['from'];
        }
        if (!empty($f['to'])) {
            $w[] = "$p$dateCol <= ?";
            $params[] = $f['to'];
        }
        return implode(' AND ', $w);
    }
}
