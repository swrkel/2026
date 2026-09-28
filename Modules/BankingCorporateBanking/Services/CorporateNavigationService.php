<?php

namespace Modules\BankingCorporateBanking\Services;

class CorporateNavigationService
{
    public function menu(): array
    {
        return [
            ['label' => 'Corporate Dashboard', 'route' => 'banking.corporate.dashboard', 'permission' => 'banking.corporate.view'],
            ['label' => 'Corporate Customers', 'route' => 'banking.corporate.corporates.index', 'permission' => 'banking.corporate.customers.view'],
            ['label' => 'Signatories', 'route' => 'banking.corporate.signatories.index', 'permission' => 'banking.corporate.signatories.view'],
            ['label' => 'Approvals', 'route' => 'banking.corporate.approvals.index', 'permission' => 'banking.corporate.approvals.view'],
            ['label' => 'Bulk Payments', 'route' => 'banking.corporate.bulk_payments.index', 'permission' => 'banking.corporate.bulk_payments.view'],
            ['label' => 'Payroll', 'route' => 'banking.corporate.payroll.index', 'permission' => 'banking.corporate.payroll.view'],
            ['label' => 'Cash Management', 'route' => 'banking.corporate.cash_management.index', 'permission' => 'banking.corporate.cash_management.view'],
            ['label' => 'Collections', 'route' => 'banking.corporate.collections.index', 'permission' => 'banking.corporate.collections.view'],
            ['label' => 'Reports', 'route' => 'banking.corporate.reports.index', 'permission' => 'banking.corporate.reports.view'],
            ['label' => 'Settings', 'route' => 'banking.corporate.settings.index', 'permission' => 'banking.corporate.settings.manage'],
        ];
    }
}
