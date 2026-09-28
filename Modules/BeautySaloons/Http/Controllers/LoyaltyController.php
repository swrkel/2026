<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\BeautySaloons\Services\LoyaltyService;

class LoyaltyController extends Controller
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    public function index()
    {
        return view('beautysaloons::loyalty.index');
    }

    public function tiers()
    {
        return view('beautysaloons::loyalty.tiers');
    }

    public function statement($customerId)
    {
        $summary = $this->loyaltyService->customerSummary((int) $customerId);
        return view('beautysaloons::loyalty.statement', compact('summary', 'customerId'));
    }

    public function earn(Request $request)
    {
        $this->loyaltyService->earn($request->all());
        return response()->json(['success' => true, 'msg' => __('beautysaloons::loyalty.points_added')]);
    }

    public function redeem(Request $request)
    {
        $this->loyaltyService->redeem($request->all());
        return response()->json(['success' => true, 'msg' => __('beautysaloons::loyalty.points_redeemed')]);
    }
}
