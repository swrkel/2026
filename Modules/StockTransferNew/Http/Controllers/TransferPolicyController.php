<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\TransferPolicy;
use Modules\StockTransferNew\Services\TransferPolicyService;

class TransferPolicyController extends Controller
{
    protected TransferPolicyService $service;

    public function __construct(TransferPolicyService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $summary = $this->service->policySummary($businessId);
        $policies = TransferPolicy::where('business_id', $businessId)->orderByDesc('id')->paginate(25);

        return view('stocktransfernew::policies.index', compact('summary', 'policies'));
    }

    public function store(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $data = $request->validate([
            'policy_name' => 'required|string|max:191',
            'from_location_id' => 'nullable|integer',
            'to_location_id' => 'nullable|integer',
            'from_store_id' => 'nullable|integer',
            'to_store_id' => 'nullable|integer',
            'priority' => 'required|in:normal,urgent,critical',
            'max_transfer_value' => 'nullable|numeric|min:0',
            'requires_cost_allocation' => 'nullable|boolean',
            'requires_cancellation_approval' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $data['business_id'] = $businessId;
        $data['requires_cost_allocation'] = $request->boolean('requires_cost_allocation');
        $data['requires_cancellation_approval'] = $request->boolean('requires_cancellation_approval');
        $data['created_by'] = auth()->id();

        TransferPolicy::create($data);

        return redirect()->back()->with('status', __('stocktransfernew::policies.saved'));
    }

    public function costCenterSummary(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $rows = DB::table('stn_transfer_cost_allocations')
            ->where('business_id', $businessId)
            ->selectRaw('cost_center_id, COUNT(*) as transfers, COALESCE(SUM(total_cost),0) as total_cost')
            ->groupBy('cost_center_id')
            ->orderByDesc('total_cost')
            ->get();

        return response()->json(['data' => $rows]);
    }
}
