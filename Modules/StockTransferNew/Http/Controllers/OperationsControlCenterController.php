<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\OperationsControlCenterService;

class OperationsControlCenterController extends Controller
{
    protected OperationsControlCenterService $service;

    public function __construct(OperationsControlCenterService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['business_id', 'location_id', 'store_id', 'status', 'priority', 'date_from', 'date_to']);
        return view('stocktransfernew::operations_control.index', $this->service->dashboard($filters));
    }

    public function liveBoard(Request $request)
    {
        return response()->json($this->service->liveBoard($request->all()));
    }

    public function exceptions(Request $request)
    {
        $filters = $request->only(['business_id', 'location_id', 'store_id', 'exception_type', 'date_from', 'date_to']);
        return view('stocktransfernew::operations_control.exceptions', $this->service->exceptions($filters));
    }

    public function escalate(Request $request, int $transferId)
    {
        $request->validate(['remarks' => 'nullable|string|max:1000']);
        $this->service->escalate($transferId, $request->input('remarks'));
        return back()->with('status', __('stocktransfernew::messages.escalation_saved'));
    }

    public function workload(Request $request)
    {
        $filters = $request->only(['business_id', 'location_id', 'store_id', 'date_from', 'date_to']);
        return view('stocktransfernew::operations_control.workload', $this->service->workload($filters));
    }

    public function calendar(Request $request)
    {
        return view('stocktransfernew::operations_control.calendar', $this->service->calendar($request->all()));
    }

    public function exportCsv(Request $request)
    {
        $csv = $this->service->exportCsv($request->all());
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="stock_transfer_operations_control.csv"',
        ]);
    }
}
