<?php

namespace Modules\EnterpriseFramework\Services\Admin;

class ReportAdministrationService
{
    public function menuGroups(): array
    {
        return [
            'executive' => 'Executive',
            'financial_statements' => 'Financial Statements',
            'ledgers' => 'Ledgers',
            'cash_banking' => 'Cash & Banking',
            'receivables' => 'Receivables',
            'payables' => 'Payables',
            'fixed_assets' => 'Fixed Assets',
            'audit' => 'Audit & Compliance',
            'forecast' => 'Budget & Forecast',
            'administration' => 'Administration',
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'default_branch_mode' => 'consolidated',
            'allow_export' => true,
            'allow_print' => true,
            'audit_report_views' => true,
            'cache_minutes' => 15,
        ];
    }
}
