<?php

namespace Modules\PetroDirectNew\Services;

use Modules\PetroDirectNew\Reports\AssignmentsReport;
use Modules\PetroDirectNew\Reports\CollectionsReport;
use Modules\PetroDirectNew\Reports\DipReadingsReport;
use Modules\PetroDirectNew\Reports\MeterSalesReport;
use Modules\PetroDirectNew\Reports\OperatorsReport;
use Modules\PetroDirectNew\Reports\PaymentsReport;
use Modules\PetroDirectNew\Reports\PumpsReport;
use Modules\PetroDirectNew\Reports\SettlementsReport;
use Modules\PetroDirectNew\Reports\ShiftsReport;
use Modules\PetroDirectNew\Reports\TankTransfersReport;
use Modules\PetroDirectNew\Reports\PumperDayEntriesReport;
use Modules\PetroDirectNew\Reports\UnloadStocksReport;
use Modules\PetroDirectNew\Reports\AdjustmentsReport;
use Modules\PetroDirectNew\Reports\TanksReport;

class ReportService
{
    public function __construct(
        private SettlementsReport $settlements,
        private PaymentsReport $payments,
        private MeterSalesReport $meterSales,
        private OperatorsReport $operators,
        private ShiftsReport $shifts,
        private AssignmentsReport $assignments,
        private PumpsReport $pumps,
        private TanksReport $tanks,
        private CollectionsReport $collections,
        private DipReadingsReport $dipReadings,
        private TankTransfersReport $tankTransfers,
        private PumperDayEntriesReport $pumperDayEntries,
        private UnloadStocksReport $unloadStocks,
        private AdjustmentsReport $adjustments
    ) {}

    public function registry(): array
    {
        return [
            $this->settlements, $this->payments, $this->meterSales, $this->operators,
            $this->shifts, $this->assignments, $this->pumps, $this->tanks,
            $this->collections, $this->dipReadings, $this->tankTransfers,
            $this->pumperDayEntries, $this->unloadStocks, $this->adjustments,
        ];
    }

    public function types(): array
    {
        $types = [];
        foreach ($this->registry() as $report) $types[$report->key()] = $report->label();
        return $types;
    }

    public function rows(string $type, array $filters = [])
    {
        foreach ($this->registry() as $report) {
            if ($report->key() === $type) return $report->rows($filters);
        }
        abort(404);
    }
}
