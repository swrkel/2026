<?php

namespace Modules\StockTransferNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\ReplenishmentVersionService;

class ReplenishmentVersionController extends Controller
{
    protected ReplenishmentVersionService $service;

    public function __construct(ReplenishmentVersionService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $versions = $this->service->listForBusiness($businessId, $request->only(['status','risk_level','location_id','store_id','product_id','per_page']));
        return view('stocktransfernew::recommendation_versions.index', compact('versions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|integer',
            'store_id' => 'required|integer',
            'product_id' => 'required|integer',
            'recommended_qty' => 'required|numeric|min:0.0001',
            'approved_qty' => 'nullable|numeric|min:0.0001',
            'confidence_score' => 'nullable|numeric|min:0|max:100',
            'risk_level' => 'nullable|in:low,medium,high,critical',
            'recommendation_source' => 'nullable|string|max:50',
            'version_reason' => 'nullable|string|max:1000',
        ]);

        $this->service->createNewVersion((int)$request->session()->get('user.business_id'), $data, (int)auth()->id());
        return back()->with('status', __('stocktransfernew::messages.replenishment_version_created'));
    }

    public function approve(Request $request, int $id)
    {
        $request->validate(['approved_qty' => 'required|numeric|min:0.0001', 'remarks' => 'nullable|string|max:500']);
        $this->service->approveVersion((int)$request->session()->get('user.business_id'), $id, (float)$request->approved_qty, (int)auth()->id(), $request->remarks);
        return back()->with('status', __('stocktransfernew::messages.replenishment_version_approved'));
    }

    public function convert(Request $request, int $id)
    {
        $request->validate(['remarks' => 'nullable|string|max:500']);
        $this->service->convertToTransferRequest((int)$request->session()->get('user.business_id'), $id, (int)auth()->id(), $request->remarks);
        return back()->with('status', __('stocktransfernew::messages.replenishment_version_converted'));
    }

    public function cancel(Request $request, int $id)
    {
        $request->validate(['remarks' => 'nullable|string|max:500']);
        $this->service->cancelVersion((int)$request->session()->get('user.business_id'), $id, (int)auth()->id(), $request->remarks);
        return back()->with('status', __('stocktransfernew::messages.replenishment_version_cancelled'));
    }
}
