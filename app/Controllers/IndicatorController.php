<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\PeriodFilter;
use App\Services\IndicatorService;

final class IndicatorController extends BaseController
{
    public function water(Request $r): void
    {
        Response::success((new IndicatorService())->consumption('WATER_M3', PeriodFilter::from($r)));
    }

    public function energy(Request $r): void
    {
        Response::success((new IndicatorService())->consumption('ENERGY_KWH', PeriodFilter::from($r)));
    }

    public function waste(Request $r): void
    {
        Response::success((new IndicatorService())->waste(PeriodFilter::from($r)));
    }

    public function goals(Request $r): void
    {
        $g = (new IndicatorService())->goals(PeriodFilter::from($r));
        Response::success($g);
    }
}
