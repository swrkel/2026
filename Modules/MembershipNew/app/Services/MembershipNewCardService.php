<?php

namespace Modules\MembershipNew\app\Services;

use Modules\MembershipNew\app\Models\MembershipNewIdentityCard;

class MembershipNewCardService
{
    public function issue(int $businessId, int $memberId, ?string $expiresOn = null): MembershipNewIdentityCard
    {
        $cardNo = config('membershipnew.card_code_prefix', 'MNC') . '-' . $businessId . '-' . str_pad((string) $memberId, 8, '0', STR_PAD_LEFT);

        MembershipNewIdentityCard::where('business_id', $businessId)
            ->where('member_id', $memberId)
            ->update(['is_active' => 0]);

        return MembershipNewIdentityCard::create([
            'business_id' => $businessId,
            'member_id' => $memberId,
            'card_no' => $cardNo,
            'qr_payload' => $cardNo,
            'issued_on' => now()->toDateString(),
            'expires_on' => $expiresOn,
            'is_active' => 1,
        ]);
    }
}
