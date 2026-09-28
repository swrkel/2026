<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Entities\LogisticsRateCard;
use Modules\StockTransferNew\Services\LogisticsRateCardService;

class LogisticsRateCardController extends Controller
{
    protected LogisticsRateCardService $service;

    public function __construct(LogisticsRateCardService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): Renderable
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $cards = $this->service->activeCards($businessId, $request->only(['location_id', 'store_id', 'transporter_name']));
        $variances = $this->service->varianceSummary($businessId, $request->only(['status']));

        return view('stocktransfernew::logistics_contracts.index', compact('cards', 'variances'));
    }

    public function store(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) optional($request->user())->id;

        $data = $request->validate([
            'location_id' => 'nullable|integer',
            'store_id' => 'nullable|integer',
            'transporter_name' => 'required|string|max:191',
            'route_code' => 'nullable|string|max:80',
            'route_name' => 'nullable|string|max:191',
            'vehicle_type' => 'nullable|string|max:80',
            'rate_basis' => 'required|string|max:40',
            'base_rate' => 'nullable|numeric|min:0',
            'rate_per_km' => 'nullable|numeric|min:0',
            'rate_per_kg' => 'nullable|numeric|min:0',
            'rate_per_cbm' => 'nullable|numeric|min:0',
            'minimum_charge' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'status' => 'required|in:active,inactive,draft',
        ]);

        $this->service->saveCard($businessId, $data, $userId);

        return back()->with('status', ['success' => 1, 'msg' => __('stocktransfernew::lang.rate_card_saved')]);
    }

    public function variance(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) optional($request->user())->id;

        $data = $request->validate([
            'transfer_id' => 'nullable|integer',
            'freight_invoice_id' => 'nullable|integer',
            'rate_card_id' => 'required|integer',
            'expected_amount' => 'required|numeric|min:0',
            'actual_amount' => 'required|numeric|min:0',
            'status' => 'nullable|in:open,accepted,disputed,closed',
            'review_note' => 'nullable|string|max:1000',
        ]);

        $this->service->createVarianceReview($businessId, $data, $userId);

        return back()->with('status', ['success' => 1, 'msg' => __('stocktransfernew::lang.variance_saved')]);
    }

    public function deactivate(Request $request, LogisticsRateCard $rateCard)
    {
        abort_unless((int) $rateCard->business_id === (int) $request->session()->get('user.business_id'), 403);
        $rateCard->update(['status' => 'inactive', 'updated_by' => optional($request->user())->id]);

        return back()->with('status', ['success' => 1, 'msg' => __('stocktransfernew::lang.rate_card_deactivated')]);
    }
}
