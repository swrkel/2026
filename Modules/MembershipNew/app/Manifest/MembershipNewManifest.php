<?php

namespace Modules\MembershipNew\app\Manifest;

class MembershipNewManifest
{
    public static function version(): string
    {
        return 'MEMNEW_026';
    }

    /**
     * Page-level capabilities exposed to ERP module/sidebar discovery.
     * Keep the core user pages in this catalogue as well as the advanced pages;
     * otherwise generic sidebar discovery can omit Dashboard/Members/Plans/etc.
     */
    public static function modules(): array
    {
        return [
            'Dashboard',
            'Members',
            'Membership Plans',
            'Membership Payments',
            'Linked Businesses / Outlets',
            'Point Rules',
            'Point Earn / Redeem',
            'Shares',
            'Dividend Batches',
            'Dividend Payouts',
            'Identity Cards',
            'Customer Sync',
            'Reports',
            'Membership Settings',
            'Central Registry',
            'Business Member Mapping',
            'Business Customer History / Ledger',
            'Business Customer Statement',
            'Business Customer Balances',
            'Duplicate Detection',
            'Member Merge',
            'Outlet Transactions',
            'Command Center',
            'Business Access',
            'Card Lifecycle',
            'Approvals',
            'Audit Logs',
            'Health Check',
            'Admin Tools',
            'Error Logs',
            'Import Templates',
            'Final Audit',
            'Handover',
            'Demo Testing',
        ];
    }

    public static function routeFiles(): array
    {
        return config('membershipnew.route_files', []);
    }

    public static function sqlFiles(): array
    {
        return [
            'MembershipNew_MEMNEW_001.sql',
            'MembershipNew_MEMNEW_002.sql',
            'MembershipNew_MEMNEW_003.sql',
            'MembershipNew_MEMNEW_004.sql',
            'MembershipNew_MEMNEW_005.sql',
            'MembershipNew_MEMNEW_006.sql',
            'MembershipNew_MEMNEW_007.sql',
            'MembershipNew_MEMNEW_008.sql',
            'MembershipNew_MEMNEW_009.sql',
            'MembershipNew_MEMNEW_010.sql',
            'MembershipNew_MEMNEW_011.sql',
            'MembershipNew_MEMNEW_012.sql',
            'MembershipNew_MEMNEW_013.sql',
        ];
    }
}
