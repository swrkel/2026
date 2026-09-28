<?php

namespace Modules\MembershipNew\app\Permissions;

class MembershipNewPermissionRegistry
{
    /**
     * Complete Membership-New permission registry.
     *
     * Keep every route permission here so Manage Page / Role / health tooling does
     * not silently omit operational pages such as Members, Plans, Payments,
     * Linked Businesses, Point Rules or Reports.
     */
    public static function grouped(): array
    {
        return [
            'Dashboard' => [
                'membership_new.dashboard.view',
            ],
            'Members' => [
                'membership_new.members.view',
                'membership_new.members.create',
                'membership_new.members.edit',
                'membership_new.members.delete',
            ],
            'Plans' => [
                'membership_new.plans.view',
                'membership_new.plans.create',
                'membership_new.plans.edit',
                'membership_new.plans.delete',
            ],
            'Payments' => [
                'membership_new.payments.view',
                'membership_new.payments.create',
                'membership_new.payments.edit',
                'membership_new.payments.delete',
            ],
            'Central Members' => [
                'membership_new.central_members.view',
                'membership_new.central_members.create',
                'membership_new.central_members.edit',
            ],
            'Business Members' => [
                'membership_new.business_members.view',
                'membership_new.business_members.link',
            ],
            'Business History' => [
                'membership_new.business_history.view',
                'membership_new.business_history.create',
                'membership_new.business_statement.view',
            ],
            'Linked Businesses' => [
                'membership_new.linked_businesses.view',
                'membership_new.linked_businesses.create',
                'membership_new.linked_businesses.edit',
                'membership_new.linked_businesses.delete',
            ],
            'Point Rules' => [
                'membership_new.point_rules.view',
                'membership_new.point_rules.create',
                'membership_new.point_rules.edit',
                'membership_new.point_rules.delete',
            ],
            'Points' => [
                'membership_new.points.view',
                'membership_new.points.earn',
                'membership_new.points.redeem',
            ],
            'Shares' => [
                'membership_new.shares.view',
                'membership_new.shares.create',
                'membership_new.shares.edit',
                'membership_new.shares.delete',
            ],
            'Dividends' => [
                'membership_new.dividends.view',
                'membership_new.dividends.create',
                'membership_new.dividends.edit',
                'membership_new.dividends.delete',
                'membership_new.dividend_payouts.view',
                'membership_new.dividend_payouts.pay',
                'membership_new.dividend_payouts.reverse',
            ],
            'Cards' => [
                'membership_new.cards.issue',
                'membership_new.cards.print',
                'membership_new.cards.scan',
            ],
            'Customer Sync' => [
                'membership_new.customer_sync.view',
                'membership_new.customer_sync.run',
            ],
            'Reports' => [
                'membership_new.reports.view',
                'membership_new.exports.download',
            ],
            'Membership Settings' => [
                'membership_new.settings.view',
                'membership_new.settings.create',
            ],
            'Governance / Admin' => [
                'membership_new.business_access.edit',
                'membership_new.approvals.view',
                'membership_new.approvals.create',
                'membership_new.approvals.approve',
                'membership_new.approvals.reject',
                'membership_new.audit.view',
                'membership_new.health.view',
                'membership_new.admin_tools.view',
                'membership_new.admin_tools.run',
                'membership_new.error_logs.view',
                'membership_new.imports.view',
            ],
        ];
    }

    public static function flat(): array
    {
        return collect(self::grouped())->flatten()->unique()->values()->all();
    }
}
