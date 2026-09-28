<?php

namespace Modules\PetroPDNew\Services\Reports;

use Modules\PetroPDNew\Reports\AdjustedAmountsReport;
use Modules\PetroPDNew\Reports\CollectionsReport;
use Modules\PetroPDNew\Reports\DayEndReport;
use Modules\PetroPDNew\Reports\DayEntriesReport;
use Modules\PetroPDNew\Reports\ExcessCommissionsReport;
use Modules\PetroPDNew\Reports\IntegrationReport;
use Modules\PetroPDNew\Reports\MeterSalesReport;
use Modules\PetroPDNew\Reports\OperatorLedgerReport;
use Modules\PetroPDNew\Reports\OperatorPaymentsReport;
use Modules\PetroPDNew\Reports\OperatorSummaryReport;
use Modules\PetroPDNew\Reports\OtherSalesReport;
use Modules\PetroPDNew\Reports\PaymentReconciliationReport;
use Modules\PetroPDNew\Reports\PrintHistoryReport;
use Modules\PetroPDNew\Reports\SettlementPaymentsReport;
use Modules\PetroPDNew\Reports\SettlementsReport;
use Modules\PetroPDNew\Reports\ShiftsReport;
use Modules\PetroPDNew\Reports\ShortageRecoveriesReport;
use Modules\PetroPDNew\Reports\SourceIntegrityReport;
use Modules\PetroPDNew\Reports\UnloadStockReport;
use Modules\PetroPDNew\Reports\UserActivityReport;
use Modules\PetroPDNew\Reports\Contracts\PdnewReport;
use RuntimeException;

class PdnewReportRegistry
{
    private const REPORTS = [
        'settlements' => SettlementsReport::class,
        'shifts' => ShiftsReport::class,
        'operators' => OperatorSummaryReport::class,
        'operator_payments' => OperatorPaymentsReport::class,
        'payments' => SettlementPaymentsReport::class,
        'meters' => MeterSalesReport::class,
        'other_sales' => OtherSalesReport::class,
        'unloads' => UnloadStockReport::class,
        'day_entries' => DayEntriesReport::class,
        'collections' => CollectionsReport::class,
        'ledger' => OperatorLedgerReport::class,
        'shortages' => ShortageRecoveriesReport::class,
        'commissions' => ExcessCommissionsReport::class,
        'day_end' => DayEndReport::class,
        'reconciliation' => PaymentReconciliationReport::class,
        'adjustments' => AdjustedAmountsReport::class,
        'integrity' => SourceIntegrityReport::class,
        'activity' => UserActivityReport::class,
        'integration' => IntegrationReport::class,
        'print_history' => PrintHistoryReport::class,
    ];

    public function all(): array
    {
        $reports = [];
        foreach (self::REPORTS as $key => $class) {
            $report = app($class);
            $reports[$key] = [
                'key' => $report->key(),
                'title' => $report->title(),
                'permission' => $report->permission(),
            ];
        }
        return $reports;
    }

    public function get(string $key): PdnewReport
    {
        $class = self::REPORTS[$key] ?? null;
        if (! $class) throw new RuntimeException('Unknown Petro PD-New report: ' . $key);

        return app($class);
    }
}
