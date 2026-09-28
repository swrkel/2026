<?php

namespace Modules\FinanceReports\Services\Finalization;

use Modules\FinanceReports\Services\FinanceReportsDataService;
use Modules\FinanceReports\Services\Engine\FinanceReportContext;

class FinancialPackService
{
    public function build(FinanceReportsDataService $dataService, int $businessId, FinanceReportContext $context): array
    {
        $locationId = $context->is_consolidated ? null : $context->location_id;

        return [
            'title' => 'Finance Reports Financial Pack',
            'mode' => $context->is_consolidated ? 'Consolidated' : 'Branch / Location',
            'period' => $context->start_date . ' to ' . $context->end_date,
            'reports' => [
                'Trial Balance - New' => $dataService->trialBalance($businessId, null, $context->as_at, $locationId),
                'Balance Sheet - New' => $dataService->balanceSheet($businessId, $context->as_at, $locationId),
                'Profit & Loss - New' => $dataService->incomeStatement($businessId, $context->start_date, $context->end_date, $locationId),
                'Income Statement - New' => $dataService->incomeStatement($businessId, $context->start_date, $context->end_date, $locationId),
            ],
            'sections' => [
                'Statement Pack',
                'Cash Flow Summary',
                'Financial Ratios',
                'Receivable / Payable Summary',
                'Branch Performance Summary',
            ],
        ];
    }
}
