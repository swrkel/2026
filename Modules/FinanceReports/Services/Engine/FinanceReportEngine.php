<?php

namespace Modules\FinanceReports\Services\Engine;

use Modules\FinanceReports\Services\FinanceReportsDataService;

class FinanceReportEngine
{
    protected FinanceReportsDataService $data;
    protected BranchConsolidationService $branches;

    public function __construct(FinanceReportsDataService $data, BranchConsolidationService $branches)
    {
        $this->data = $data;
        $this->branches = $branches;
    }

    public function contextFromRequest($request, int $business_id): FinanceReportContext
    {
        return FinanceReportContext::fromRequest($request, $business_id);
    }

    public function statementPack(FinanceReportContext $context): array
    {
        return [
            'trial_balance' => $this->data->trialBalance($context->business_id, null, $context->as_at, $context->location_id),
            'balance_sheet' => $this->data->balanceSheet($context->business_id, $context->as_at, $context->location_id),
            'income_statement' => $this->data->incomeStatement($context->business_id, $context->start_date, $context->end_date, $context->location_id),
            'cash_flow' => method_exists($this->data, 'cashFlowStatement')
                ? $this->data->cashFlowStatement($context->business_id, $context->start_date, $context->end_date, $context->location_id)
                : null,
        ];
    }

    public function executiveDashboard(FinanceReportContext $context): array
    {
        $pack = $this->statementPack($context);
        $pl = $pack['income_statement'];
        $bs = $pack['balance_sheet'];

        $income = (float) data_get($pl, 'totals.income', 0);
        $revenue = (float) data_get($pl, 'totals.revenue', $income);
        $other_income = (float) data_get($pl, 'totals.other_income', $income - $revenue);
        $expenses = (float) data_get($pl, 'totals.expenses', 0);
        $gross_profit = (float) data_get($pl, 'totals.gross_profit', $revenue);
        $net_profit = (float) data_get($pl, 'totals.net_profit', $income - $expenses);
        $assets = (float) data_get($bs, 'totals.assets', 0);
        $liabilities = (float) data_get($bs, 'totals.liabilities', 0);
        $equity = (float) data_get($bs, 'totals.equity', 0);
        $current_assets = (float) data_get($bs, 'totals.current_assets', 0);
        $current_liabilities = (float) data_get($bs, 'totals.current_liabilities', 0);
        $working_capital = $current_assets - $current_liabilities;

        return [
            'context' => $context,
            'pack' => $pack,
            'cards' => [
                ['label' => 'Revenue', 'value' => $revenue, 'route' => 'finance-reports.revenue-analysis-new'],
                ['label' => 'Expenses', 'value' => $expenses, 'route' => 'finance-reports.expense-analysis-new'],
                ['label' => 'Net Profit / Loss', 'value' => $net_profit, 'route' => 'finance-reports.income-statement-new'],
                ['label' => 'Total Assets', 'value' => $assets, 'route' => 'finance-reports.balance-sheet-new'],
                ['label' => 'Total Liabilities', 'value' => $liabilities, 'route' => 'finance-reports.balance-sheet-new'],
                ['label' => 'Equity', 'value' => $equity, 'route' => 'finance-reports.balance-sheet-new'],
                ['label' => 'Working Capital', 'value' => $working_capital, 'route' => 'finance-reports.financial-ratios-new'],
                ['label' => 'Trial Balance Difference', 'value' => (float) data_get($pack, 'trial_balance.totals.difference', 0), 'route' => 'finance-reports.trial-balance-new'],
            ],
            'health' => [
                'current_ratio' => abs($current_liabilities) < 0.0001 ? null : round($current_assets / $current_liabilities, 4),
                'net_margin' => abs($revenue) < 0.0001 ? null : round(($net_profit / $revenue) * 100, 4),
                'debt_ratio' => $assets == 0.0 ? null : round(($liabilities / max($assets, 0.0001)) * 100, 4),
            ],
        ];
    }



    public function enterpriseCenter(FinanceReportContext $context): array
    {
        return [
            'dashboard' => $this->executiveDashboard($context),
            'consolidation' => $this->consolidationCenter($context),
            'performance' => $this->performanceCenter($context),
            'forecast_cash_flow' => $this->forecast($context, 'cash_flow', 6),
            'forecast_profit' => $this->forecast($context, 'profit', 6),
            'release' => 'FinanceReports Enterprise RC-1',
            'read_only' => true,
            'existing_finance_module_touched' => false,
        ];
    }

    public function forecast(FinanceReportContext $context, string $type = 'profit', int $months = 6): array
    {
        return $this->data->forecast($context->business_id, $context->start_date, $context->end_date, $context->location_id, $type, $months);
    }

    public function consolidationCenter(FinanceReportContext $context): array
    {
        $locations = $this->data->locations($context->business_id);
        $rows = [];
        foreach ($locations as $id => $name) {
            $pl = $this->data->incomeStatement($context->business_id, $context->start_date, $context->end_date, $id);
            $bs = $this->data->balanceSheet($context->business_id, $context->as_at, $id);
            $rows[] = [
                'location_id' => $id,
                'location' => $name,
                'income' => (float) data_get($pl, 'totals.income', 0),
                'expenses' => (float) data_get($pl, 'totals.expenses', 0),
                'net_profit' => (float) data_get($pl, 'totals.net_profit', 0),
                'assets' => (float) data_get($bs, 'totals.assets', 0),
                'liabilities' => (float) data_get($bs, 'totals.liabilities', 0),
                'equity' => (float) data_get($bs, 'totals.equity', 0),
                'unallocated' => false,
            ];
        }

        // A consolidated report can legitimately contain legacy/manual postings
        // that have no resolvable source location. Never force those postings into
        // every branch. Show the exact consolidated-minus-branch difference as a
        // transparent Unallocated/Global row so branch totals reconcile to the
        // consolidated financial statements without guessing a location.
        $consolidatedPl = $this->data->incomeStatement($context->business_id, $context->start_date, $context->end_date, null);
        $consolidatedBs = $this->data->balanceSheet($context->business_id, $context->as_at, null);
        $consolidated = [
            'income' => (float) data_get($consolidatedPl, 'totals.income', 0),
            'expenses' => (float) data_get($consolidatedPl, 'totals.expenses', 0),
            'net_profit' => (float) data_get($consolidatedPl, 'totals.net_profit', 0),
            'assets' => (float) data_get($consolidatedBs, 'totals.assets', 0),
            'liabilities' => (float) data_get($consolidatedBs, 'totals.liabilities', 0),
            'equity' => (float) data_get($consolidatedBs, 'totals.equity', 0),
        ];

        $branchSums = [
            'income' => array_sum(array_column($rows, 'income')),
            'expenses' => array_sum(array_column($rows, 'expenses')),
            'net_profit' => array_sum(array_column($rows, 'net_profit')),
            'assets' => array_sum(array_column($rows, 'assets')),
            'liabilities' => array_sum(array_column($rows, 'liabilities')),
            'equity' => array_sum(array_column($rows, 'equity')),
        ];
        $unallocated = [];
        foreach ($consolidated as $key => $value) {
            $unallocated[$key] = round($value - ($branchSums[$key] ?? 0), 4);
        }
        $hasUnallocated = collect($unallocated)->contains(fn ($value) => abs((float) $value) > 0.0001);
        if ($hasUnallocated) {
            $rows[] = array_merge([
                'location_id' => null,
                'location' => 'Unallocated / Global (No resolvable source location)',
                'unallocated' => true,
            ], $unallocated);
        }

        return [
            'mode' => 'Branch wise + Consolidated',
            'rows' => $rows,
            'totals' => array_map(fn ($value) => round((float) $value, 4), $consolidated),
            'difference' => round((float) data_get($consolidatedBs, 'totals.difference', 0), 4),
            'has_unallocated' => $hasUnallocated,
        ];
    }

    public function performanceCenter(FinanceReportContext $context): array
    {
        return [
            'status' => $this->status($context),
            'query_strategy' => [
                'read_only_reporting' => true,
                'branch_location_filter' => true,
                'consolidated_mode' => true,
                'large_dataset_pagination' => true,
                'future_background_exports' => true,
            ],
            'recommended_indexes' => [
                'account_transactions: business_id, operation_date, account_id, transaction_id, transaction_payment_id, deleted_at',
                'accounts: business_id, id, account_type_id, asset_type',
                'transactions: business_id, transaction_date, location_id, type, status',
                'business_locations: business_id, id',
            ],
            'cache_keys' => [
                'statement_pack_by_business_branch_period',
                'trial_balance_by_business_branch_period',
                'balance_sheet_by_business_branch_as_at',
                'income_statement_by_business_branch_period',
                'dashboard_by_business_branch_period',
            ],
        ];
    }

    public function status(FinanceReportContext $context): array
    {
        return [
            'engine' => 'Finance Reports Core Engine',
            'mode' => $context->is_consolidated ? 'Consolidated' : 'Branch / Location',
            'date_range' => $context->start_date . ' to ' . $context->end_date,
            'as_at' => $context->as_at,
            'read_only' => true,
            'existing_finance_module_touched' => false,
            'shared_services' => [
                'FinanceReportEngine',
                'FinanceReportContext',
                'BranchConsolidationService',
                'CurrencyFormatterService',
                'ForecastingEngine',
                'ConsolidationCenter',
                'PerformanceCenter',
            ],
        ];
    }
}
