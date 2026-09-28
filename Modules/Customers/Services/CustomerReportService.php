<?php

namespace Modules\Customers\Services;

use Modules\Customers\Reports\CustomerActivityReport;
use Modules\Customers\Reports\CustomerAgingReport;
use Modules\Customers\Reports\CustomerLedgerReport;
use Modules\Customers\Reports\CustomerStatementReport;

class CustomerReportService
{
    protected $ledgerService;
    protected $ledgerReport;
    protected $statementReport;
    protected $agingReport;
    protected $activityReport;

    public function __construct(
        CustomerLedgerService $ledgerService,
        CustomerLedgerReport $ledgerReport,
        CustomerStatementReport $statementReport,
        CustomerAgingReport $agingReport,
        CustomerActivityReport $activityReport
    ) {
        $this->ledgerService = $ledgerService;
        $this->ledgerReport = $ledgerReport;
        $this->statementReport = $statementReport;
        $this->agingReport = $agingReport;
        $this->activityReport = $activityReport;
    }

    public function reportPages(): array
    {
        return [
            'index' => 'customers::reports.index',
            'list' => 'customers::reports.customer-list',
            'ledger' => 'customers::reports.customer-ledger',
            'statement' => 'customers::reports.customer-statement',
            'aging' => 'customers::reports.customer-aging',
            'inactive' => 'customers::reports.inactive-customers',
        ];
    }

    public function indexData(int $businessId): array
    {
        return [
            'summary' => $this->ledgerService->summary($businessId),
            'aging' => $this->ledgerService->agingSummary($businessId),
        ];
    }

    public function customerListData(int $businessId): array
    {
        return $this->activityReport->customerListData($businessId);
    }

    public function ledgerData(
        int $businessId,
        ?int $customerId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        return $this->ledgerReport->data($businessId, $customerId, $startDate, $endDate);
    }

    public function statementData(int $businessId, ?int $customerId = null): array
    {
        return $this->statementReport->data($businessId, $customerId);
    }

    public function agingData(int $businessId): array
    {
        return $this->agingReport->data($businessId);
    }

    public function inactiveCustomersData(int $businessId): array
    {
        return $this->activityReport->inactiveCustomersData($businessId);
    }
}
