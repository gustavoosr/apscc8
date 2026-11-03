<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\OrgResourceController;
use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use App\Helpers\Calc;
use App\Helpers\OrgScope;

/** Base para consumo mensal (água/energia). Vários lançamentos por setor/mês são somados nos indicadores. */
abstract class ConsumptionController extends OrgResourceController
{
    protected string $valueColumn;
    protected string $joins = 'JOIN sectors s ON s.id = t.sector_id';
    protected string $defaultSort = '-reference_month';

    protected function filters(Request $r, array &$where, array &$params): void
    {
        if ($s = $r->q('sector_id')) {
            $where[] = 't.sector_id = ?';
            $params[] = (int) $s;
        }
        if ($f = $r->q('from')) {
            $where[] = 't.reference_month >= ?';
            $params[] = $this->month((string) $f, 'from');
        }
        if ($t = $r->q('to')) {
            $where[] = 't.reference_month <= ?';
            $params[] = $this->month((string) $t, 'to');
        }
    }

    /** GET .../compare?a_from=&a_to=&b_from=&b_to= : compara o total de dois períodos. */
    public function compare(Request $r): void
    {
        $orgs = OrgScope::ids($r);
        $a = $this->total($orgs, $r, 'a');
        $b = $this->total($orgs, $r, 'b');
        Response::success([
            'period_a' => ['from' => $this->month((string) $r->q('a_from'), 'a_from'), 'to' => $this->month((string) $r->q('a_to'), 'a_to'), 'total' => $a],
            'period_b' => ['from' => $this->month((string) $r->q('b_from'), 'b_from'), 'to' => $this->month((string) $r->q('b_to'), 'b_to'), 'total' => $b],
            'variation_pct' => Calc::variation($b, $a),
            'note' => 'Variação de B em relação a A. Nulo quando A não possui dados.',
        ]);
    }

    private function total(array $orgs, Request $r, string $p): float
    {
        $params = [];
        $where = [OrgScope::in('organization_id', $orgs, $params), 'deleted_at IS NULL', 'reference_month BETWEEN ? AND ?'];
        array_push($params, $this->month((string) $r->q("{$p}_from"), "{$p}_from"), $this->month((string) $r->q("{$p}_to"), "{$p}_to"));
        if ($s = $r->q('sector_id')) {
            $where[] = 'sector_id = ?';
            $params[] = (int) $s;
        }
        $st = Database::pdo()->prepare("SELECT COALESCE(SUM({$this->valueColumn}),0) FROM {$this->table} WHERE " . implode(' AND ', $where));
        $st->execute($params);
        return round((float) $st->fetchColumn(), 3);
    }

    protected function month(string $v, string $field): string
    {
        if (preg_match('/^\d{4}-\d{2}$/', $v)) {
            $v .= '-01';
        }
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $v);
        if (!$d || $d->format('Y-m-d') !== $v) {
            throw new ValidationException([['field' => $field, 'message' => 'Informe o mês no formato AAAA-MM.']]);
        }
        return $d->format('Y-m-01');
    }
}
