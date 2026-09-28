<?php

namespace Modules\FinanceReports\Services\Finalization;

class ProductionReadinessService
{
    public function calculationChecklist(): array
    {
        return [
            ['area' => 'Trial Balance', 'check' => 'Debit total equals Credit total and agrees with the account ledger closing balances for the same branch/as-at date.'],
            ['area' => 'Balance Sheet', 'check' => 'Assets equal Liabilities + Equity for branch and consolidated modes.'],
            ['area' => 'Profit & Loss', 'check' => 'Income, expenses and net profit agree with ledger source data.'],
            ['area' => 'Income Statement', 'check' => 'Same statement logic as P&L, separate presentation only.'],
            ['area' => 'Cash Flow', 'check' => 'Opening cash + movement equals closing cash.'],
            ['area' => 'Ledgers', 'check' => 'Opening balance, running balance and closing balance are correct.'],
            ['area' => 'Branch Consolidation', 'check' => 'Physical branch totals plus any explicitly disclosed Unallocated / Global postings agree with the consolidated result.'],
        ];
    }

    public function readinessChecklist(): array
    {
        return [
            'Standalone module files remain under Modules/FinanceReports.',
            'Existing Finance controllers/routes/views are not replaced.',
            'All reports are read-only by design.',
            'Branch/location and consolidated filters are available in reporting pages.',
            'Permission keys are separated from existing Finance permissions.',
            'Export and print centers are available.',
            'Drill-down center is read-only.',
            'Financial pack, scheduler framework and KPI center are included.',
            'Final README and deployment checklist are included.',
        ];
    }
}
