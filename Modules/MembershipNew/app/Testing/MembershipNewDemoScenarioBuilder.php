<?php

namespace Modules\MembershipNew\app\Testing;

use Illuminate\Support\Facades\DB;

class MembershipNewDemoScenarioBuilder
{
    public function summary(): array
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();

        return [
            'business_id' => $businessId,
            'central_members' => DB::table('mn_central_members')->count(),
            'business_members' => DB::table('mn_member_business_maps')->where('business_id', $businessId)->count(),
            'point_rules' => DB::table('mn_point_rules')->where('business_id', $businessId)->count(),
            'point_transactions' => DB::table('mn_point_transactions')->where('business_id', $businessId)->count(),
            'share_holdings' => DB::table('mn_share_holdings')->where('business_id', $businessId)->count(),
            'dividend_batches' => DB::table('mn_dividend_batches')->where('business_id', $businessId)->count(),
            'identity_cards' => DB::table('mn_identity_cards')->where('business_id', $businessId)->count(),
        ];
    }
}
