<?php

namespace Modules\MembershipNew\app\Reports;

class MembershipNewReportCenter
{
    public function reports(): array
    {
        return [
            ['title' => 'Business Customer Balances', 'route' => 'membership-new.business-balances.index', 'description' => 'Current business ledger and point balances.', 'icon' => 'fa-balance-scale'],
            ['title' => 'Business Customer Statement', 'route' => 'membership-new.business-statement.index', 'description' => 'Detailed business-specific customer statement.', 'icon' => 'fa-list-alt'],
            ['title' => 'Member Point Balances', 'route' => 'membership-new.reports.member-balances', 'description' => 'Member-wise current point balances.', 'icon' => 'fa-star'],
            ['title' => 'Point Ledger', 'route' => 'membership-new.reports.point-ledger', 'description' => 'Earn and redeem point transaction history.', 'icon' => 'fa-exchange'],
            ['title' => 'Share Register', 'route' => 'membership-new.reports.share-register', 'description' => 'Member shareholding register.', 'icon' => 'fa-pie-chart'],
            ['title' => 'Dividend Register', 'route' => 'membership-new.reports.dividend-register', 'description' => 'Dividend payment register and status.', 'icon' => 'fa-line-chart'],
            ['title' => 'Dividend Payouts', 'route' => 'membership-new.dividend-payouts.index', 'description' => 'Dividend payment and reversal history.', 'icon' => 'fa-money'],
            ['title' => 'Audit Log', 'route' => 'membership-new.audit.index', 'description' => 'Membership New activity audit trail.', 'icon' => 'fa-shield'],
            ['title' => 'Error Logs', 'route' => 'membership-new.error-logs.index', 'description' => 'Membership New captured error records.', 'icon' => 'fa-exclamation-triangle'],
        ];
    }
}
