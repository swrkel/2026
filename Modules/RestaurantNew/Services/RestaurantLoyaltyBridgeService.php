<?php

namespace Modules\RestaurantNew\Services;

use Modules\RestaurantNew\Entities\RestaurantNewLoyaltyLink;

class RestaurantLoyaltyBridgeService
{
    public function earnPoints(int $customerProfileId, float $amount, array $context = []): RestaurantNewLoyaltyLink
    {
        $points = round($amount * ((float)($context['points_rate'] ?? 0.01)), 4);
        $link = RestaurantNewLoyaltyLink::firstOrCreate(
            ['customer_profile_id' => $customerProfileId],
            ['business_id' => $context['business_id'], 'location_id' => $context['location_id'] ?? null, 'linked_at' => now()]
        );
        $link->available_points += $points;
        $link->lifetime_points += $points;
        $link->integration_meta = array_merge($link->integration_meta ?? [], ['last_earn_context' => $context]);
        $link->save();
        return $link;
    }

    public function redeemPoints(int $customerProfileId, float $points, array $context = []): bool
    {
        $link = RestaurantNewLoyaltyLink::where('customer_profile_id', $customerProfileId)->first();
        if (!$link || $link->available_points < $points) {
            return false;
        }
        $link->available_points -= $points;
        $link->integration_meta = array_merge($link->integration_meta ?? [], ['last_redeem_context' => $context]);
        $link->save();
        return true;
    }
}
