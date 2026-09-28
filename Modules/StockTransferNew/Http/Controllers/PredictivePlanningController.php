<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\PredictivePlanningService;

class PredictivePlanningController extends Controller
{
    protected PredictivePlanningService $service;

    public function __construct(PredictivePlanningService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['business_id', 'location_id', 'store_id', 'product_id', 'date_from', 'date_to']);
        return view('stocktransfernew::predictive_planning.index', $this->service->dashboard($filters));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'business_id' => 'required|integer',
            'from_location_id' => 'nullable|integer',
            'to_location_id' => 'required|integer',
            'from_store_id' => 'nullable|integer',
            'to_store_id' => 'required|integer',
            'forecast_days' => 'required|integer|min:1|max:180',
            'safety_stock_days' => 'nullable|integer|min:0|max:90',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $plan = $this->service->generatePlan($data);
        return redirect()->route('stocktransfernew.predictive-planning.show', $plan->id)
            ->with('status', __('stocktransfernew::messages.predictive_plan_generated'));
    }

    public function show(int $planId)
    {
        return view('stocktransfernew::predictive_planning.show', $this->service->show($planId));
    }

    public function approveLine(Request $request, int $lineId)
    {
        $request->validate(['approved_qty' => 'required|numeric|min:0', 'remarks' => 'nullable|string|max:1000']);
        $this->service->approveLine($lineId, $request->input('approved_qty'), $request->input('remarks'));
        return back()->with('status', __('stocktransfernew::messages.predictive_line_approved'));
    }

    public function rejectLine(Request $request, int $lineId)
    {
        $request->validate(['remarks' => 'required|string|max:1000']);
        $this->service->rejectLine($lineId, $request->input('remarks'));
        return back()->with('status', __('stocktransfernew::messages.predictive_line_rejected'));
    }

    public function workloadBalance(Request $request)
    {
        $filters = $request->only(['business_id', 'location_id', 'store_id', 'date_from', 'date_to']);
        return view('stocktransfernew::predictive_planning.workload_balance', $this->service->workloadBalance($filters));
    }

    public function exportCsv(Request $request)
    {
        $csv = $this->service->exportCsv($request->all());
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="stock_transfer_predictive_planning.csv"',
        ]);
    }
}
