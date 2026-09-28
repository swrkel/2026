<?php

namespace Modules\PumperDashboardNew\Http\Controllers\Operator;

use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Services\PoneContextService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;

class ReportController extends Controller
{
    public function __construct(private PoneContextService $context, private PoneSharedMasterDataService $masterData) {}

    public function metersWithPayments()
    {
        $shift = $this->context->shift();
        $shift->load(['assignments', 'payments']);
        $pumps = $this->masterData->pumps($shift->business_id, $shift->location_id)->keyBy('id');
        return view('pumperdashboardnew::operator.reports.meters-with-payments', compact('shift', 'pumps'));
    }
}
