<?php

namespace Modules\MembershipNew\app\Services;

use Modules\MembershipNew\app\Models\MembershipNewIdentityCard;

class MembershipNewCardLifecycleService
{
    public function block(int $businessId, int $cardId, ?string $reason = null): MembershipNewIdentityCard
    {
        $card = MembershipNewIdentityCard::forBusiness($businessId)->findOrFail($cardId);
        $card->update([
            'is_active' => 0,
            'blocked_at' => now(),
            'blocked_by' => auth()->id(),
            'blocked_reason' => $reason,
        ]);

        return $card->fresh();
    }

    public function replace(int $businessId, int $oldCardId, MembershipNewCardService $cardService): MembershipNewIdentityCard
    {
        $old = $this->block($businessId, $oldCardId, 'Replaced with a new card.');
        return $cardService->issue($businessId, $old->member_id);
    }

    public function touchScan(int $businessId, string $cardNo): ?MembershipNewIdentityCard
    {
        $card = MembershipNewIdentityCard::forBusiness($businessId)
            ->where('card_no', $cardNo)
            ->where('is_active', 1)
            ->first();

        if ($card) {
            $card->update([
                'last_scanned_at' => now(),
                'last_scanned_by' => auth()->id(),
            ]);
        }

        return $card;
    }
}
