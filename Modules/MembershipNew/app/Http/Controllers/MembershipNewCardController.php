<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewIdentityCard;
use Modules\MembershipNew\app\Models\MembershipNewMember;
use Modules\MembershipNew\app\Services\MembershipNewCardService;

class MembershipNewCardController extends Controller
{
    public function issue(Request $request, int $memberId, MembershipNewCardService $service)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        MembershipNewMember::forBusiness($businessId)->findOrFail($memberId);
        $service->issue($businessId, $memberId, $request->expires_on);
        return redirect()->route('membership-new.cards.print', $memberId)->with('status', __('membershipnew::messages.card_issued'));
    }

    public function print(int $memberId)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $member = MembershipNewMember::forBusiness($businessId)->findOrFail($memberId);
        $card = MembershipNewIdentityCard::forBusiness($businessId)->where('member_id', $memberId)->where('is_active', 1)->latest()->first();

        return view('membershipnew::cards.print', compact('member', 'card'));
    }

    public function scan(string $cardNo)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $card = MembershipNewIdentityCard::forBusiness($businessId)->where('card_no', $cardNo)->where('is_active', 1)->firstOrFail();

        return response()->json([
            'success' => true,
            'member_id' => $card->member_id,
            'card_no' => $card->card_no,
            'expires_on' => optional($card->expires_on)->format('Y-m-d'),
        ]);
    }
}
