<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\PeriodFilter;
use App\Services\IndicatorService;

final class DashboardController extends BaseController
{
    /** Cards do dashboard. */
    public function summary(Request $r): void
    {
        $f = PeriodFilter::from($r);
        $svc = new IndicatorService();
        $water = $svc->consumption('WATER_M3', $f);
        $energy = $svc->consumption('ENERGY_KWH', $f);
        $waste = $svc->waste($f);
        $goals = $svc->goals($f);
        Response::success([
            'water' => ['total_m3' => $water['total'], 'variation_pct' => $water['variation_pct'], 'has_data' => $water['has_data']],
            'energy' => ['total_kwh' => $energy['total'], 'variation_pct' => $energy['variation_pct'], 'has_data' => $energy['has_data']],
            'waste' => ['total_kg' => $waste['total_kg'], 'has_data' => $waste['has_data']],
            'recycling' => ['pct' => $waste['recycling_pct'], 'recycled_kg' => $waste['recycled_kg']],
            'goals' => ['total' => $goals['total'], 'achieved' => $goals['achieved'], 'in_progress' => $goals['in_progress'], 'late' => $goals['late'], 'average_progress_pct' => $goals['average_progress_pct']],
        ]);
    }

    /** Dados dos gráficos. */
    public function charts(Request $r): void
    {
        $f = PeriodFilter::from($r);
        $svc = new IndicatorService();
        $waste = $svc->waste($f);
        Response::success([
            'water_monthly' => $svc->consumption('WATER_M3', $f)['history'],
            'energy_monthly' => $svc->consumption('ENERGY_KWH', $f)['history'],
            'waste_monthly' => $waste['history'],
            'waste_by_destination' => $waste['by_destination'],
            'goals_evolution' => $svc->goalsEvolution($f),
        ]);
    }
}
