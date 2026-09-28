<?php

namespace Modules\EnterpriseFramework\Services\Adapter;

use Modules\EnterpriseFramework\Contracts\ModuleReportAdapterContract;

class FinanceReportsAdapter implements ModuleReportAdapterContract
{
    public function moduleKey(): string { return 'finance_reports'; }
    public function moduleName(): string { return 'Finance Reports'; }

    public function reports(): array
    {
        return [
            ['key' => 'trial_balance_new', 'name' => 'Trial Balance - New', 'category' => 'Financial Statements'],
            ['key' => 'balance_sheet_new', 'name' => 'Balance Sheet - New', 'category' => 'Financial Statements'],
            ['key' => 'profit_loss_new', 'name' => 'Profit & Loss - New', 'category' => 'Financial Statements'],
            ['key' => 'income_statement_new', 'name' => 'Income Statement - New', 'category' => 'Financial Statements'],
            ['key' => 'general_ledger_new', 'name' => 'General Ledger - New', 'category' => 'Ledgers'],
            ['key' => 'cash_book_new', 'name' => 'Cash Book - New', 'category' => 'Cash & Banking'],
            ['key' => 'bank_book_new', 'name' => 'Bank Book - New', 'category' => 'Cash & Banking'],
            ['key' => 'financial_intelligence', 'name' => 'Financial Intelligence', 'category' => 'Executive'],
        ];
    }

    public function dashboards(): array
    {
        return [
            ['key' => 'executive_dashboard', 'name' => 'Executive Dashboard'],
            ['key' => 'cfo_dashboard', 'name' => 'CFO Dashboard'],
            ['key' => 'financial_health_score', 'name' => 'Financial Health Score'],
        ];
    }

    public function metrics(array $context = []): array
    {
        return [
            'module' => $this->moduleName(),
            'reports' => count($this->reports()),
            'dashboards' => count($this->dashboards()),
            'read_only' => true,
            'branch_context' => $context['location_id'] ?? 'consolidated',
        ];
    }

    public function data(string $reportKey, array $context = []): array
    {
        return [
            'report_key' => $reportKey,
            'context' => $context,
            'rows' => [],
            'message' => 'Finance Reports adapter is connected. Report-specific data remains served by the FinanceReports module services.',
        ];
    }

    public function health(): array
    {
        return [
            'module' => $this->moduleName(),
            'status' => 'connected',
            'read_only' => true,
            'dependency_mode' => 'adapter_contract_only',
        ];
    }
}
