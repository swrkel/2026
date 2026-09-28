<?php

namespace Modules\MembershipNew\app\Menu;

class MembershipNewNavigationRegistry
{
    /**
     * Membership-New sidebar page registry.
     *
     * Keep real page routes here only (not create/edit/action endpoints).  The
     * host sidebar partial checks route existence and the logged-in user's
     * permission before rendering each page.
     */
    public static function sidebar(): array
    {
        return [
            'module' => 'Membership-New',
            'icon' => 'fa fa-id-card',
            'permission' => 'membership_new.dashboard.view',
            'route' => 'membership-new.dashboard',
            'items' => [
                // Main day-to-day pages.
                ['title' => 'Dashboard', 'route' => 'membership-new.dashboard', 'permission' => 'membership_new.dashboard.view'],
                ['title' => 'Members', 'route' => 'membership-new.members.index', 'permission' => 'membership_new.members.view'],
                ['title' => 'Plans', 'route' => 'membership-new.plans.index', 'permission' => 'membership_new.plans.view'],
                ['title' => 'Payments', 'route' => 'membership-new.payments.index', 'permission' => 'membership_new.payments.view'],
                ['title' => 'Linked Businesses', 'route' => 'membership-new.linked-businesses.index', 'permission' => 'membership_new.linked_businesses.view'],
                ['title' => 'Point Rules', 'route' => 'membership-new.point-rules.index', 'permission' => 'membership_new.point_rules.view'],
                ['title' => 'Points', 'route' => 'membership-new.points.index', 'permission' => 'membership_new.points.view'],
                ['title' => 'Shares', 'route' => 'membership-new.shares.index', 'permission' => 'membership_new.shares.view'],
                ['title' => 'Dividends', 'route' => 'membership-new.dividends.index', 'permission' => 'membership_new.dividends.view'],
                ['title' => 'Dividend Payouts', 'route' => 'membership-new.dividend-payouts.index', 'permission' => 'membership_new.dividends.view'],
                ['title' => 'Card Scan', 'route' => 'membership-new.cards.scan-page', 'permission' => 'membership_new.cards.scan'],
                ['title' => 'Customer Sync', 'route' => 'membership-new.customer-sync.index', 'permission' => 'membership_new.customer_sync.view'],
                ['title' => 'Reports', 'route' => 'membership-new.report-center.index', 'permission' => 'membership_new.reports.view'],

                // Central / multi-business pages.
                ['title' => 'Central Members', 'route' => 'membership-new.central-members.index', 'permission' => 'membership_new.central_members.view'],
                ['title' => 'Business Members', 'route' => 'membership-new.business-members.index', 'permission' => 'membership_new.business_members.view'],
                ['title' => 'Business History', 'route' => 'membership-new.business-history.index', 'permission' => 'membership_new.business_history.view'],
                ['title' => 'Business Statement', 'route' => 'membership-new.business-statement.index', 'permission' => 'membership_new.business_history.view'],
                ['title' => 'Business Balances', 'route' => 'membership-new.business-balances.index', 'permission' => 'membership_new.business_history.view'],
                ['title' => 'Duplicate Candidates', 'route' => 'membership-new.central-members.duplicates', 'permission' => 'membership_new.central_members.view'],
                ['title' => 'Member Merge', 'route' => 'membership-new.member-merge.index', 'permission' => 'membership_new.central_members.edit'],
                ['title' => 'Outlet Transactions', 'route' => 'membership-new.outlet-transactions.index', 'permission' => 'membership_new.points.view'],

                // Governance / administration pages.
                ['title' => 'Command Center', 'route' => 'membership-new.command-center.index', 'permission' => 'membership_new.dashboard.view'],
                ['title' => 'Business Access', 'route' => 'membership-new.business-access.edit', 'permission' => 'membership_new.business_access.edit'],
                ['title' => 'Card Lifecycle', 'route' => 'membership-new.card-lifecycle.index', 'permission' => 'membership_new.cards.issue'],
                ['title' => 'Approvals', 'route' => 'membership-new.approvals.index', 'permission' => 'membership_new.approvals.view'],
                ['title' => 'Audit Log', 'route' => 'membership-new.audit.index', 'permission' => 'membership_new.audit.view'],
                ['title' => 'Health Check', 'route' => 'membership-new.health.index', 'permission' => 'membership_new.health.view'],
                ['title' => 'Admin Tools', 'route' => 'membership-new.admin-tools.index', 'permission' => 'membership_new.admin_tools.view'],
                ['title' => 'Error Logs', 'route' => 'membership-new.error-logs.index', 'permission' => 'membership_new.error_logs.view'],
                ['title' => 'Import Templates', 'route' => 'membership-new.imports.index', 'permission' => 'membership_new.imports.view'],
                ['title' => 'Final Audit', 'route' => 'membership-new.final-audit.index', 'permission' => 'membership_new.health.view'],
                ['title' => 'Handover', 'route' => 'membership-new.handover.index', 'permission' => 'membership_new.health.view'],
                ['title' => 'Demo', 'route' => 'membership-new.demo.index', 'permission' => 'membership_new.health.view'],

                // Keep Membership Settings as the last sidebar page.
                ['title' => 'Membership Settings', 'route' => 'membership-new.settings.index', 'permission' => 'membership_new.settings.view'],
            ],
        ];
    }
}
