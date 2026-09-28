<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewCustomerProfile;
use Modules\RestaurantNew\Entities\RestaurantNewCustomerVisit;
use Modules\RestaurantNew\Entities\RestaurantNewLoyaltyLink;
use Modules\RestaurantNew\Entities\RestaurantNewCrmCampaign;

class RestaurantCrmService
{
    public function dashboard(int $businessId, ?int $locationId = null): array
    {
        $profiles = RestaurantNewCustomerProfile::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId));

        $visits = RestaurantNewCustomerVisit::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId));

        return [
            'total_customers' => (clone $profiles)->count(),
            'vip_customers' => (clone $profiles)->whereNotNull('vip_level')->count(),
            'returning_customers' => (clone $profiles)->where('visit_count', '>', 1)->count(),
            'lost_customers' => (clone $profiles)->where('last_visit_at', '<', now()->subDays(90))->count(),
            'total_visits' => (clone $visits)->count(),
            'total_revenue' => (clone $visits)->sum('net_total'),
            'active_campaigns' => RestaurantNewCrmCampaign::where('business_id', $businessId)->where('status', 'active')->count(),
        ];
    }

    public function customer360(int $customerProfileId): array
    {
        $profile = RestaurantNewCustomerProfile::findOrFail($customerProfileId);
        $visits = RestaurantNewCustomerVisit::where('customer_profile_id', $customerProfileId)->latest('visited_at')->limit(25)->get();
        $loyalty = RestaurantNewLoyaltyLink::where('customer_profile_id', $customerProfileId)->first();

        return compact('profile', 'visits', 'loyalty');
    }

    public function syncVisitTotals(int $customerProfileId): void
    {
        $totals = RestaurantNewCustomerVisit::where('customer_profile_id', $customerProfileId)
            ->selectRaw('COUNT(*) as visit_count, COALESCE(SUM(net_total),0) as lifetime_spend, COALESCE(AVG(net_total),0) as average_bill_value, MAX(visited_at) as last_visit_at')
            ->first();

        RestaurantNewCustomerProfile::where('id', $customerProfileId)->update([
            'visit_count' => (int) $totals->visit_count,
            'lifetime_spend' => $totals->lifetime_spend,
            'average_bill_value' => $totals->average_bill_value,
            'last_visit_at' => $totals->last_visit_at,
        ]);
    }

    public function segments(int $businessId, ?int $locationId = null): array
    {
        $base = RestaurantNewCustomerProfile::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId));

        return [
            'new' => (clone $base)->where('visit_count', '<=', 1)->count(),
            'frequent' => (clone $base)->where('visit_count', '>=', 5)->count(),
            'high_value' => (clone $base)->where('lifetime_spend', '>=', 100000)->count(),
            'inactive_30_days' => (clone $base)->where('last_visit_at', '<', now()->subDays(30))->count(),
            'birthdays' => 0,
            'anniversaries' => 0,
        ];
    }
}
