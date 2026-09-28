<?php

namespace Modules\StockTransferNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\AiReplenishmentReviewService;

class AiReplenishmentReviewController extends Controller
{
    protected AiReplenishmentReviewService $service;

    public function __construct(AiReplenishmentReviewService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $reviews = $this->service->listForBusiness($businessId, $request->only(['status','risk_level','store_id','per_page']));

        return view('stocktransfernew::ai_replenishment.index', compact('reviews'));
    }

    public function approve(Request $request, int $id)
    {
        $request->validate([
            'approved_qty' => 'required|numeric|min:0.0001',
            'remarks' => 'nullable|string|max:500',
        ]);

        $this->service->approve(
            (int) $request->session()->get('user.business_id'),
            $id,
            (float) $request->approved_qty,
            (int) auth()->id(),
            $request->remarks
        );

        return back()->with('status', __('stocktransfernew::messages.ai_replenishment_approved'));
    }

    public function reject(Request $request, int $id)
    {
        $request->validate(['remarks' => 'nullable|string|max:500']);
        $this->service->reject((int) $request->session()->get('user.business_id'), $id, (int) auth()->id(), $request->remarks);
        return back()->with('status', __('stocktransfernew::messages.ai_replenishment_rejected'));
    }

    public function returnForCorrection(Request $request, int $id)
    {
        $request->validate(['remarks' => 'nullable|string|max:500']);
        $this->service->returnForCorrection((int) $request->session()->get('user.business_id'), $id, (int) auth()->id(), $request->remarks);
        return back()->with('status', __('stocktransfernew::messages.ai_replenishment_returned'));
    }
}
