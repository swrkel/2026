<?php

namespace Modules\MembershipNew\app\Services;

use Modules\MembershipNew\app\Models\MembershipNewBusinessAccessRule;

class MembershipNewBusinessAccessService
{
    public function rule(int $businessId): MembershipNewBusinessAccessRule
    {
        return MembershipNewBusinessAccessRule::firstOrCreate(
            ['business_id' => $businessId],
            [
                'can_view_central_profile' => 1,
                'can_view_other_business_history' => 0,
                'can_redeem_cross_business_points' => 1,
                'can_issue_card' => 1,
                'is_active' => 1,
            ]
        );
    }

    public function updateRule(int $businessId, array $data): MembershipNewBusinessAccessRule
    {
        $rule = $this->rule($businessId);
        $rule->update([
            'can_view_central_profile' => !empty($data['can_view_central_profile']),
            'can_view_other_business_history' => !empty($data['can_view_other_business_history']),
            'can_redeem_cross_business_points' => !empty($data['can_redeem_cross_business_points']),
            'can_issue_card' => !empty($data['can_issue_card']),
            'is_active' => !empty($data['is_active']),
            'note' => $data['note'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        return $rule->fresh();
    }
}
