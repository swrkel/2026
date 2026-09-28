<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\PumperDashboardNew\Reports\AuditTrailReport;
use Modules\PumperDashboardNew\Reports\CollectionReport;
use Modules\PumperDashboardNew\Reports\DayEntryReport;
use Modules\PumperDashboardNew\Reports\ExcessCommissionReport;
use Modules\PumperDashboardNew\Reports\LedgerReport;
use Modules\PumperDashboardNew\Reports\MeterSalesReport;
use Modules\PumperDashboardNew\Reports\OtherSalesReport;
use Modules\PumperDashboardNew\Reports\PaymentSummaryReport;
use Modules\PumperDashboardNew\Reports\PoneReport;
use Modules\PumperDashboardNew\Reports\PrintLogReport;
use Modules\PumperDashboardNew\Reports\ShiftSummaryReport;
use Modules\PumperDashboardNew\Reports\ShortageRecoveryReport;
use Modules\PumperDashboardNew\Reports\UnloadStockReport;

class PoneReportService
{
    /** @var array<string,PoneReport> */
    private array $reports = [];

    public function __construct(
        ShiftSummaryReport $shifts,
        PaymentSummaryReport $payments,
        MeterSalesReport $meters,
        OtherSalesReport $otherSales,
        UnloadStockReport $unloads,
        DayEntryReport $dayEntries,
        CollectionReport $collections,
        LedgerReport $ledger,
        ShortageRecoveryReport $shortages,
        ExcessCommissionReport $commissions,
        PrintLogReport $printLogs,
        AuditTrailReport $audit
    ) {
        foreach ([$shifts, $payments, $meters, $otherSales, $unloads, $dayEntries, $collections, $ledger, $shortages, $commissions, $printLogs, $audit] as $report) {
            $this->reports[$report->key()] = $report;
        }
    }

    public function keys(): array { return array_keys($this->reports); }

    public function labels(): array
    {
        $labels = [];
        foreach ($this->reports as $key => $report) $labels[$key] = $report->label();
        return $labels;
    }

    public function report(string $report, int $businessId, array $filters = []): Collection
    {
        if (! isset($this->reports[$report])) {
            throw new InvalidArgumentException('Unknown Pumper Dashboard-New report: ' . $report);
        }
        return $this->reports[$report]->rows($businessId, $filters);
    }
}
