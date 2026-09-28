<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewIdentityCard;
use Modules\MembershipNew\app\Services\MembershipNewPointService;

class MembershipNewCardScanController extends Controller
{
    public function page()
    {
        return view('membershipnew::cards.scan');
    }

    public function lookup(Request $request, MembershipNewPointService $pointService)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();

        $card = MembershipNewIdentityCard::with('member')
            ->forBusiness($businessId)
            ->where('card_no', $request->card_no)
            ->where('is_active', 1)
            ->first();

        if (!$card) {
            return back()->withErrors(['card_no' => 'Active membership card not found.']);
        }

        $balance = $pointService->balance($businessId, $card->member_id);

        return view('membershipnew::cards.scan', compact('card', 'balance'));
    }
}
