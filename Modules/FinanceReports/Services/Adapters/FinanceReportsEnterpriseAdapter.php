<?php

namespace Modules\FinanceReports\Services\Adapters;

use Modules\EnterpriseFramework\Contracts\ModuleReportAdapterContract;
use Modules\FinanceReports\Services\FinanceReportsDataService;

/**
 * Read-only adapter that allows Enterprise Framework to discover Finance Reports
 * without depending on Finance module controllers, routes, or views.
 */
class FinanceReportsEnterpriseAdapter implements ModuleReportAdapterContract
{
    protected ?FinanceReportsDataService $dataService;

    public function __construct(?FinanceReportsDataService $dataService = null)
    {
        $this->dataService = $dataService;
    }

    public function moduleKey(): string
    {
        return 'finance_reports';
    }

    public function moduleName(): string
    {
        return 'Finance Reports';
    }

    public function reports(): array
    {
        $routeMap = [
            'finance-reports.trial-balance' => 'finance-reports.trial-balance-new',
            'finance-reports.balance-sheet' => 'finance-reports.balance-sheet-new',
            'finance-reports.profit-loss' => 'finance-reports.profit-loss-new',
            'finance-reports.income-statement' => 'finance-reports.income-statement-new',
            'finance-reports.general-ledger' => 'finance-reports.general-ledger-new',
            'finance-reports.account-ledger' => 'finance-reports.account-ledger-new',
            'finance-reports.cash-book' => 'finance-reports.cash-book-new',
            'finance-reports.bank-book' => 'finance-reports.bank-book-new',
            'finance-reports.financial-intelligence' => 'finance-reports.financial-intelligence-new',
        ];

        return collect(config('financereports_enterprise.reports', []))
            ->map(function (array $report) use ($routeMap): array {
                $route = $report['route'] ?? null;

                if ($route && isset($routeMap[$route])) {
                    $report['route'] = $routeMap[$route];
                }

                return $report;
            })
            ->values()
            ->all();
    }

    public function dashboards(): array
    {
        return [
            ['key' => 'finance_reports.executive_dashboard', 'name' => 'Executive BI Dashboard', 'route' => 'finance-reports.executive-bi-dashboard-new'],
            ['key' => 'finance_reports.financial_intelligence', 'name' => 'Financial Intelligence', 'route' => 'finance-reports.financial-intelligence-new'],
            ['key' => 'finance_reports.cfo_dashboard', 'name' => 'CFO Dashboard', 'route' => 'finance-reports.cfo-dashboard-new'],
        ];
    }

    public function metrics(array $context = []): array
    {
        return [
            'module' => $this->moduleName(),
            'read_only' => true,
            'branch_supported' => true,
            'consolidated_supported' => true,
            'registered_reports' => count($this->reports()),
            'dashboards' => count($this->dashboards()),
        ];
    }

    public function data(string $reportKey, array $context = []): array
    {
        // Adapter intentionally returns metadata/context only unless a dedicated
        // read-only report data provider is added for the report key.
        return [
            'report_key' => $reportKey,
            'context' => $context,
            'read_only' => true,
            'source' => 'finance_reports_adapter',
        ];
    }

    public function health(): array
    {
        return [
            'module' => $this->moduleName(),
            'status' => 'ready',
            'integration' => 'enterprise_framework_adapter',
            'read_only_guard' => true,
            'existing_finance_module_touched' => false,
        ];
    }
}
