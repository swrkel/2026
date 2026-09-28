<?php

namespace Modules\MembershipNew\app\Integration;

use Modules\MembershipNew\app\Services\MembershipNewPointService;

class MembershipNewPurchaseIntegration
{
    public function __construct(private MembershipNewPointService $pointService)
    {
    }

    public function preview(array $payload): array
    {
        $points = $this->pointService->previewEarnPoints(
            (int) $payload['business_id'],
            $payload['outlet_business_id'] ?? null,
            $payload['location_id'] ?? null,
            $payload['category_id'] ?? null,
            (float) ($payload['purchase_amount'] ?? 0)
        );

        return [
            'member_id' => $payload['member_id'] ?? null,
            'earn_points' => $points,
            'redeem_points' => (float) ($payload['redeem_points'] ?? 0),
        ];
    }

    public function confirm(array $payload): array
    {
        $preview = $this->preview($payload);

        if (!empty($payload['member_id']) && $preview['earn_points'] > 0) {
            $this->pointService->earn([
                'business_id' => (int) $payload['business_id'],
                'member_id' => (int) $payload['member_id'],
                'outlet_business_id' => $payload['outlet_business_id'] ?? null,
                'location_id' => $payload['location_id'] ?? null,
                'category_id' => $payload['category_id'] ?? null,
                'purchase_amount' => (float) ($payload['purchase_amount'] ?? 0),
                'points' => $preview['earn_points'],
                'reference_type' => $payload['reference_type'] ?? null,
                'reference_id' => $payload['reference_id'] ?? null,
                'source_module' => $payload['source_module'] ?? null,
                'note' => 'Membership-New purchase integration earn entry',
            ]);
        }

        if (!empty($payload['member_id']) && $preview['redeem_points'] > 0) {
            $this->pointService->redeem([
                'business_id' => (int) $payload['business_id'],
                'member_id' => (int) $payload['member_id'],
                'outlet_business_id' => $payload['outlet_business_id'] ?? null,
                'location_id' => $payload['location_id'] ?? null,
                'category_id' => $payload['category_id'] ?? null,
                'purchase_amount' => (float) ($payload['purchase_amount'] ?? 0),
                'points' => $preview['redeem_points'],
                'reference_type' => $payload['reference_type'] ?? null,
                'reference_id' => $payload['reference_id'] ?? null,
                'source_module' => $payload['source_module'] ?? null,
                'note' => 'Membership-New purchase integration redeem entry',
            ]);
        }

        return ['success' => true] + $preview;
    }
}
