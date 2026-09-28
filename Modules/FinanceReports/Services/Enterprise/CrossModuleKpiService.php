<?php

namespace Modules\FinanceReports\Services\Enterprise;

class CrossModuleKpiService
{
    public function dashboard(): array
    {
        return [
            'revenue' => ['Daily Revenue', 'Monthly Revenue', 'Annual Revenue', 'Revenue Growth'],
            'expense' => ['Expense Growth', 'Operating Cost Ratio', 'Branch Expense Mix'],
            'profit' => ['Gross Margin', 'Operating Margin', 'Net Margin', 'Branch Profitability'],
            'cash' => ['Cash Position', 'Bank Position', 'Liquidity Risk', 'Cash Forecast'],
            'working_capital' => ['Receivables', 'Payables', 'Inventory Value', 'Working Capital'],
            'cross_module' => ['Fuel Margin', 'Delivery Profitability', 'Customer Profitability', 'Membership Revenue', 'Clinic Revenue'],
        ];
    }

    public function insightRules(): array
    {
        return [
            'Revenue movement compared with previous period',
            'Expenses rising faster than revenue',
            'Branch profitability ranking',
            'Receivable ageing deterioration',
            'Cash risk based on current trend',
            'Inventory holding and stock value movement',
            'Module-specific contribution to consolidated finance results',
        ];
    }
}
