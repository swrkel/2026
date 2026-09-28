<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;

class MembershipNewInstallVerifier
{
    public function verifyTables(): array
    {
        $required = [
            'mn_members',
            'mn_central_members',
            'mn_member_business_maps',
            'mn_business_customer_histories',
            'mn_linked_businesses',
            'mn_point_rules',
            'mn_point_transactions',
            'mn_share_holdings',
            'mn_dividend_batches',
            'mn_dividend_payments',
            'mn_dividend_payouts',
            'mn_identity_cards',
            'mn_customer_maps',
            'mn_duplicate_candidates',
            'mn_outlet_transaction_queue',
            'mn_merge_requests',
            'mn_audit_logs',
            'mn_approval_requests',
            'mn_business_access_rules',
            'mn_error_logs',
            'mn_import_batches',
        ];

        $result = [];
        foreach ($required as $table) {
            try {
                DB::table($table)->limit(1)->count();
                $result[$table] = true;
            } catch (\Throwable $e) {
                $result[$table] = false;
            }
        }

        return $result;
    }

    public function missingTables(): array
    {
        return array_keys(array_filter($this->verifyTables(), fn ($ok) => !$ok));
    }
}
