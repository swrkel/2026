<?php

namespace Modules\MembershipNew\app\Health;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Permissions\MembershipNewPermissionRegistry;

class MembershipNewHealthCheck
{
    public function run(): array
    {
        $tables = [
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
            'mn_identity_cards',
            'mn_customer_maps',
            'mn_audit_logs',
            'mn_approval_requests',
            'mn_business_access_rules',
            'mn_error_logs',
            'mn_import_batches',
        ];

        $tableResults = [];
        foreach ($tables as $table) {
            try {
                DB::table($table)->limit(1)->count();
                $tableResults[$table] = 'OK';
            } catch (\Throwable $e) {
                $tableResults[$table] = 'MISSING';
            }
        }

        $permissionResults = [];
        foreach (MembershipNewPermissionRegistry::flat() as $permission) {
            $exists = DB::table('permissions')->where('name', $permission)->exists();
            $permissionResults[$permission] = $exists ? 'OK' : 'MISSING';
        }

        return [
            'tables' => $tableResults,
            'permissions' => $permissionResults,
            'route_files' => config('membershipnew.route_files', []),
        ];
    }
}
