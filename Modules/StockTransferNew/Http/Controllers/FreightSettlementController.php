<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\FreightSettlementService;

class FreightSettlementController extends Controller
{
    protected FreightSettlementService $service;

    public function __construct(FreightSettlementService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return response()->json($this->service->datatable($request));
        }
        return view('stocktransfernew::freight_settlement.index');
    }

    public function reconcile(Request $request, int $invoiceId)
    {
        $this->service->reconcile($invoiceId, $request->all());
        return redirect()->back()->with('status', __('stocktransfernew::lang.freight_reconciled'));
    }

    public function export(Request $request)
    {
        return $this->service->exportCsv($request);
    }
}
