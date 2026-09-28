<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionNew\Models\DisnewSettlement;
use Modules\DistributionNew\Services\Settlements\DisnewSettlementService;

class SettlementController extends Controller
{
    public function index(Request $request, DisnewSettlementService $service)
    {
        $businessId = (int) session('business.id');
        $settlements = $service->listForBusiness($businessId, $request->all())->paginate(25);
        return view('distributionnew::settlements.index', compact('settlements'));
    }

    public function create(){ return view('distributionnew::settlements.create'); }

    public function store(Request $request, DisnewSettlementService $service)
    {
        $data = $request->validate([
            'business_location_id'=>'nullable|integer','sales_rep_id'=>'nullable|integer','vehicle_id'=>'nullable|integer','settlement_date'=>'required|date',
            'opening_stock_value'=>'nullable|numeric','loaded_value'=>'nullable|numeric','sold_value'=>'nullable|numeric','returned_value'=>'nullable|numeric',
            'shortage_value'=>'nullable|numeric','excess_value'=>'nullable|numeric','note'=>'nullable|string'
        ]);
        $data['business_id'] = (int) session('business.id');
        $data['created_by'] = auth()->id();
        $service->createDraft($data);
        return redirect()->route('distribution-new.settlements.index')->with('status', __('distributionnew::messages.saved_successfully'));
    }

    public function finalize(DisnewSettlement $settlement, DisnewSettlementService $service)
    {
        $service->finalize($settlement, (int) auth()->id());
        return back()->with('status', __('distributionnew::messages.finalized_successfully'));
    }
}
