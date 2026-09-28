<?php

namespace Modules\StockTransferNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Entities\ProductionReleaseCheck;
use Modules\StockTransferNew\Entities\ReleaseSignOff;
use Modules\StockTransferNew\Services\ProductionReleaseService;

class ProductionReleaseController extends Controller
{
    private ProductionReleaseService $service;

    public function __construct(ProductionReleaseService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        $checks = ProductionReleaseCheck::where('business_id', $businessId)
            ->latest('checked_at')
            ->limit(100)
            ->get();

        $signOffs = ReleaseSignOff::where('business_id', $businessId)
            ->latest('signed_at')
            ->limit(20)
            ->get();

        return view('stocktransfernew::production_console.index', compact('checks', 'signOffs'));
    }

    public function run(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->filled('location_id') ? (int) $request->location_id : null;
        $storeId = $request->filled('store_id') ? (int) $request->store_id : null;

        $this->service->runChecks($businessId, $locationId, $storeId);

        return redirect()->back()->with('status', __('stocktransfernew::messages.production_checks_completed'));
    }

    public function signOff(Request $request)
    {
        $request->validate([
            'release_stage' => 'required|string|max:100',
            'status' => 'required|in:approved,approved_with_notes,blocked',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $businessId = (int) $request->session()->get('user.business_id');
        $this->service->signOff($businessId, $request->release_stage, $request->status, $request->remarks);

        return redirect()->back()->with('status', __('stocktransfernew::messages.production_signoff_saved'));
    }
}
