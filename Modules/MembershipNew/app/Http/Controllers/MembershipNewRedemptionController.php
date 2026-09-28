<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Services\MembershipNewPointService;
use Modules\MembershipNew\app\Services\MembershipNewRedemptionService;

class MembershipNewRedemptionController extends Controller
{
    public function preview(Request $request, MembershipNewPointService $pointService, MembershipNewRedemptionService $redemptionService)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $memberId = (int) $request->member_id;
        $invoiceAmount = (float) $request->invoice_amount;
        $pointValue = (float) $request->point_value;

        $availablePoints = $pointService->balance($businessId, $memberId);
        $maxPoints = $redemptionService->maxRedeemablePoints($availablePoints, $invoiceAmount, $pointValue);
        $amount = $redemptionService->pointValueToAmount($maxPoints, $pointValue);

        return response()->json([
            'available_points' => $availablePoints,
            'max_redeemable_points' => $maxPoints,
            'redeem_amount' => $amount,
        ]);
    }
}
