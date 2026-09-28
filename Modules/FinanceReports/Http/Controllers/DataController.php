<?php

namespace Modules\FinanceReports\Http\Controllers;

use Illuminate\Routing\Controller;

class DataController extends Controller
{
    /**
     * Package/subscription switches exposed to Superadmin packages.
     */
    public function superadmin_package(): array
    {
        return [
            [
                'name' => 'finance_reports_module',
                'label' => 'Finance Reports Module',
                'default' => false,
            ],
        ];
    }

    /**
     * Role permissions exposed in role create/edit screens.
     */
    public function user_permissions(): array
    {
        return [
            [
                'value' => 'finance_reports.view',
                'label' => 'Finance Reports - View',
                'default' => false,
            ],
            [
                'value' => 'finance_reports.print',
                'label' => 'Finance Reports - Print',
                'default' => false,
            ],
            [
                'value' => 'finance_reports.export',
                'label' => 'Finance Reports - Export',
                'default' => false,
            ],
            [
                'value' => 'finance_reports.executive_dashboard',
                'label' => 'Finance Reports - Executive Dashboard',
                'default' => false,
            ],
            [
                'value' => 'finance_reports.audit_reports',
                'label' => 'Finance Reports - Audit Reports',
                'default' => false,
            ],
            [
                'value' => 'finance_reports.forecast_reports',
                'label' => 'Finance Reports - Forecast Reports',
                'default' => false,
            ],
            [
                'value' => 'finance_reports.fixed_asset_reports',
                'label' => 'Finance Reports - Fixed Asset Reports',
                'default' => false,
            ],
            [
                'value' => 'finance_reports.consolidated_reports',
                'label' => 'Finance Reports - Consolidated Reports',
                'default' => false,
            ],
        ];
    }
}
