<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;

class MembershipNewAdminToolService
{
    public function counts(): array
    {
        $tables = [
            'mn_central_members',
            'mn_member_business_maps',
            'mn_business_customer_histories',
            'mn_point_transactions',
            'mn_share_holdings',
            'mn_dividend_batches',
            'mn_dividend_payments',
            'mn_dividend_payouts',
            'mn_identity_cards',
            'mn_customer_maps',
            'mn_audit_logs',
            'mn_error_logs',
        ];

        $counts = [];
        foreach ($tables as $table) {
            try {
                $counts[$table] = DB::table($table)->count();
            } catch (\Throwable $e) {
                $counts[$table] = 'Missing';
            }
        }

        return $counts;
    }

    public function resetDemoData(int $businessId): array
    {
        // Safe helper: deletes only obvious sample rows inserted by SAMPLE_DATA files.
        $deleted = [];
        $deleted['histories'] = DB::table('mn_business_customer_histories')
            ->where('business_id', $businessId)
            ->where('reference_type', 'sample')
            ->delete();

        $deleted['central_members'] = DB::table('mn_central_members')
            ->where('central_member_code', 'like', 'CMN-TEST-%')
            ->delete();

        return $deleted;
    }
}
