<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Finance\Services\FinanceReceivableMonitoringService;

class FinanceReceivableMonitoringController extends Controller
{
    public function run()
    {
        FinanceReceivableMonitoringService::monitor();

        return redirect()
            ->route('finance.risk.index')
            ->with('status', [
                'success' => 1,
                'msg' => 'Overdue receivable monitoring completed successfully'
            ]);
    }
}