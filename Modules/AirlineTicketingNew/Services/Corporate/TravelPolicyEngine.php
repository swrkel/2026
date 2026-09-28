<?php
namespace Modules\AirlineTicketingNew\Services\Corporate;

use Modules\AirlineTicketingNew\Entities\TravelPolicy;

class TravelPolicyEngine
{
    public function evaluate(int $businessId, int $corporateCustomerId, array $booking): array
    {
        $policy = TravelPolicy::query()
            ->where('business_id', $businessId)
            ->where('corporate_customer_id', $corporateCustomerId)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', now());
            })
            ->latest('id')
            ->first();

        if (!$policy) {
            return ['compliant' => true, 'violations' => []];
        }

        $rules = $policy->rules_json ?? [];
        $violations = [];

        if (isset($rules['max_fare']) && (float)($booking['fare'] ?? 0) > (float)$rules['max_fare']) {
            $violations[] = 'Fare exceeds the corporate maximum.';
        }

        if (!empty($rules['allowed_cabins']) && !in_array($booking['cabin'] ?? null, $rules['allowed_cabins'], true)) {
            $violations[] = 'Selected cabin is not allowed.';
        }

        if (!empty($rules['advance_purchase_days'])) {
            $days = now()->diffInDays(\Carbon\Carbon::parse($booking['departure_date'] ?? now()), false);
            if ($days < (int)$rules['advance_purchase_days']) {
                $violations[] = 'Advance purchase requirement is not met.';
            }
        }

        return [
            'compliant' => empty($violations),
            'violations' => $violations,
            'policy_id' => $policy->id,
        ];
    }
}
