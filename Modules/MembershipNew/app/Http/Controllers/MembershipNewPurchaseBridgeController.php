<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Services\MembershipNewPointService;

class MembershipNewPurchaseBridgeController extends Controller
{
    public function previewPoints(Request $request, MembershipNewPointService $service)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();

        $points = $service->previewEarnPoints(
            $businessId,
            $request->integer('outlet_business_id') ?: null,
            $request->integer('location_id') ?: null,
            $request->integer('category_id') ?: null,
            (float) $request->purchase_amount
        );

        return response()->json(['points' => $points]);
    }

    public function confirmPurchase(Request $request, MembershipNewPointService $service)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();

        $points = $service->previewEarnPoints(
            $businessId,
            $request->integer('outlet_business_id') ?: null,
            $request->integer('location_id') ?: null,
            $request->integer('category_id') ?: null,
            (float) $request->purchase_amount
        );

        if ($points > 0) {
            $service->earn([
                'business_id' => $businessId,
                'member_id' => $request->integer('member_id'),
                'outlet_business_id' => $request->integer('outlet_business_id') ?: null,
                'location_id' => $request->integer('location_id') ?: null,
                'category_id' => $request->integer('category_id') ?: null,
                'transaction_date' => now(),
                'points' => $points,
                'purchase_amount' => (float) $request->purchase_amount,
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'note' => 'Earned from purchase bridge',
            ]);
        }

        if ((float) $request->redeem_points > 0) {
            $service->redeem([
                'business_id' => $businessId,
                'member_id' => $request->integer('member_id'),
                'outlet_business_id' => $request->integer('outlet_business_id') ?: null,
                'location_id' => $request->integer('location_id') ?: null,
                'category_id' => $request->integer('category_id') ?: null,
                'transaction_date' => now(),
                'points' => (float) $request->redeem_points,
                'purchase_amount' => (float) $request->purchase_amount,
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'note' => 'Redeemed during purchase bridge',
            ]);
        }

        return response()->json(['success' => true, 'earned_points' => $points]);
    }
}
