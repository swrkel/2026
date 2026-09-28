<?php

namespace Modules\FinanceReports\Services\Security;

class FinanceReportsPermissionService
{
    public function permissions(): array
    {
        return [
            'finance_reports.view',
            'finance_reports.print',
            'finance_reports.export',
            'finance_reports.executive_dashboard',
            'finance_reports.audit_reports',
            'finance_reports.forecast_reports',
            'finance_reports.fixed_asset_reports',
            'finance_reports.consolidated_reports',
        ];
    }

    public function canViewConsolidated(): bool
    {
        return auth()->check() && (auth()->user()->can('finance_reports.consolidated_reports') || auth()->user()->can('superadmin'));
    }
}
