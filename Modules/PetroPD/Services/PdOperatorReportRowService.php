<?php

namespace Modules\PetroPD\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical operational rows shared by Petro PD Close Shift and Pumper Day Entries.
 *
 * The live tenant databases contain both legacy assignment-linked rows and newer
 * direct shift-linked rows.  This loader intentionally uses small, business-
 * scoped queries and resolves the relationships in PHP, avoiding fragile SQL
 * joins which previously caused both DataTables to return empty collections.
 */
class PdOperatorReportRowService
{
    public function dayEntryRows(
        int $businessId,
        array $shiftIds,
        int $pumpOperatorId = 0,
        bool $strictOperator = false,
        int $pumpId = 0
    ): Collection {
        $shiftIds = collect($shiftIds)
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($shiftIds)) {
            return collect();
        }

        $assignments = $this->safeSource(
            fn () => $this->loadAssignmentsForShifts($businessId, $shiftIds),
            'pump_operator_assignments',
            $businessId,
            $shiftIds
        );
        $assignmentIds = $assignments->pluck('id')->map(static fn ($id) => (int) $id)->filter()->all();

        $dayEntries = $this->safeSource(
            fn () => $this->loadDayEntries(
                $businessId,
                $shiftIds,
                $assignmentIds,
                $pumpId
            ),
            'pumper_day_entries',
            $businessId,
            $shiftIds
        );

        // A direct shift-linked day entry may still point to an old assignment
        // outside the selected shift. Load that assignment by immutable ID for
        // operator/pump enrichment, but never use it to change the selected row's
        // resolved Shift ID.
        $missingAssignmentIds = $dayEntries
            ->pluck('pumper_assignment_id')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->diff($assignmentIds)
            ->unique()
            ->values()
            ->all();

        if (! empty($missingAssignmentIds) && Schema::hasTable('pump_operator_assignments')) {
            $missingAssignments = $this->safeSource(function () use ($missingAssignmentIds, $businessId) {
                $query = DB::table('pump_operator_assignments')->whereIn('id', $missingAssignmentIds);
                $this->whereBusiness($query, 'pump_operator_assignments', $businessId);

                return $query->get();
            }, 'pump_operator_assignments_by_id', $businessId, $shiftIds);
            $assignments = $assignments->concat($missingAssignments)->unique('id')->values();
        }

        $meterSales = $this->safeSource(
            fn () => $this->loadMeterSales($businessId, $shiftIds),
            'pump_operator_meter_sales',
            $businessId,
            $shiftIds
        );
        $meterSaleDetails = $this->safeSource(
            fn () => $this->loadMeterSaleDetails(
                $businessId,
                $meterSales->pluck('id')->map(static fn ($id) => (int) $id)->filter()->all(),
                $pumpId
            ),
            'pump_operator_meter_sale_details',
            $businessId,
            $shiftIds
        );

        $settlements = $this->safeSource(
            fn () => $this->loadSettlements($assignments),
            'settlements',
            $businessId,
            $shiftIds
        );
        $shifts = $this->safeSource(
            fn () => $this->loadShifts($businessId, $shiftIds),
            'petro_shifts',
            $businessId,
            $shiftIds
        );

        $operatorIds = $assignments->pluck('pump_operator_id')
            ->concat($dayEntries->pluck('pump_operator_id'))
            ->concat($meterSales->pluck('pump_operator_id'))
            ->concat($shifts->pluck('pump_operator_id'))
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $operators = $this->safeSource(
            fn () => $this->loadOperators($businessId, $operatorIds),
            'pump_operators',
            $businessId,
            $shiftIds
        );

        $pumpIds = $assignments->pluck('pump_id')
            ->concat($dayEntries->pluck('pump_id'))
            ->concat($meterSaleDetails->pluck('pump_id'))
            ->concat($meterSales->pluck('pump_id'))
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $pumps = $this->safeSource(
            fn () => $this->loadPumps($businessId, $pumpIds),
            'pumps',
            $businessId,
            $shiftIds
        );

        $locationIds = $operators->pluck('location_id')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $locations = $this->safeSource(
            fn () => $this->loadLocations($businessId, $locationIds),
            'business_locations',
            $businessId,
            $shiftIds
        );

        $assignmentsById = $assignments->keyBy(static fn ($row) => (int) $row->id);
        $assignmentsByTuple = $assignments->groupBy(function ($row) {
            return $this->tuple(
                (int) data_get($row, 'shift_id', 0),
                (int) data_get($row, 'pump_operator_id', 0),
                (int) data_get($row, 'pump_id', 0)
            );
        });
        $assignmentsByShiftPump = $assignments->groupBy(function ($row) {
            return (int) data_get($row, 'shift_id', 0) . ':' . (int) data_get($row, 'pump_id', 0);
        });
        $assignmentsByShift = $assignments->groupBy(static fn ($row) => (int) data_get($row, 'shift_id', 0));

        $dayEntriesByAssignment = $dayEntries
            ->filter(static fn ($row) => (int) data_get($row, 'pumper_assignment_id', 0) > 0)
            ->sortByDesc(static fn ($row) => (int) data_get($row, 'id', 0))
            ->groupBy(static fn ($row) => (int) data_get($row, 'pumper_assignment_id', 0));

        $meterSalesById = $meterSales->keyBy(static fn ($row) => (int) $row->id);
        $representedDayEntryIds = [];
        $representedAssignmentIds = [];
        $representedMeterSaleIds = [];
        $rows = collect();

        foreach ($meterSaleDetails as $detail) {
            $saleId = (int) data_get($detail, 'sale_id', 0);
            $sale = $meterSalesById->get($saleId);
            if (! $sale) {
                continue;
            }

            $shiftId = (int) data_get($sale, 'shift_id', 0);
            if (! in_array($shiftId, $shiftIds, true)) {
                continue;
            }

            $saleOperatorId = (int) data_get($sale, 'pump_operator_id', 0);
            $detailPumpId = (int) data_get($detail, 'pump_id', 0);
            $assignmentGroup = $assignmentsByTuple->get(
                $this->tuple($shiftId, $saleOperatorId, $detailPumpId)
            );
            $assignment = $assignmentGroup instanceof Collection
                ? $assignmentGroup->sortByDesc('id')->first()
                : null;

            if (! $assignment) {
                $assignmentGroup = $assignmentsByShiftPump->get($shiftId . ':' . $detailPumpId);
                $assignment = $assignmentGroup instanceof Collection
                    ? $assignmentGroup->sortByDesc('id')->first()
                    : null;
            }
            if (! $assignment) {
                $assignmentGroup = $assignmentsByShift->get($shiftId);
                $assignment = $assignmentGroup instanceof Collection
                    ? $assignmentGroup->sortByDesc('id')->first()
                    : null;
            }

            $assignmentId = (int) data_get($assignment, 'id', 0);
            $dayEntryGroup = $assignmentId > 0
                ? $dayEntriesByAssignment->get($assignmentId)
                : null;
            $dayEntry = $dayEntryGroup instanceof Collection ? $dayEntryGroup->first() : null;

            if ($dayEntry) {
                $representedDayEntryIds[(int) data_get($dayEntry, 'id', 0)] = true;
            }
            if ($saleId > 0) {
                $representedMeterSaleIds[$saleId] = true;
            }
            if ($assignmentId > 0) {
                $representedAssignmentIds[$assignmentId] = true;
            }

            $operatorId = $saleOperatorId
                ?: (int) data_get($assignment, 'pump_operator_id', 0)
                ?: (int) data_get($dayEntry, 'pump_operator_id', 0);
            if ($strictOperator && $pumpOperatorId > 0 && $operatorId !== $pumpOperatorId) {
                continue;
            }
            if ($pumpId > 0 && $detailPumpId !== $pumpId) {
                continue;
            }

            $rows->push($this->sourceRow(
                $businessId,
                $shiftId,
                $operatorId,
                $detailPumpId,
                $assignment,
                $dayEntry,
                $sale,
                $detail,
                $operators,
                $pumps,
                $locations,
                $shifts,
                $settlements
            ));
        }

        /*
         * Some tenants store one meter row directly on pump_operator_meter_sales
         * without a child detail row.  Earlier implementations ignored those
         * masters completely, leaving the table empty while the summary still
         * had the shift totals.  Merge the master with its assignment/day entry
         * when possible and emit one canonical row.
         */
        foreach ($meterSales as $sale) {
            $saleId = (int) data_get($sale, 'id', 0);
            if ($saleId <= 0 || isset($representedMeterSaleIds[$saleId])) {
                continue;
            }

            $shiftId = (int) data_get($sale, 'shift_id', 0);
            if (! in_array($shiftId, $shiftIds, true)) {
                continue;
            }

            $saleOperatorId = (int) data_get($sale, 'pump_operator_id', 0);
            $salePumpId = (int) data_get($sale, 'pump_id', 0);
            $assignment = null;

            if ($salePumpId > 0) {
                $group = $assignmentsByTuple->get($this->tuple($shiftId, $saleOperatorId, $salePumpId));
                $assignment = $group instanceof Collection ? $group->sortByDesc('id')->first() : null;
                if (! $assignment) {
                    $group = $assignmentsByShiftPump->get($shiftId . ':' . $salePumpId);
                    $assignment = $group instanceof Collection ? $group->sortByDesc('id')->first() : null;
                }
            }
            if (! $assignment) {
                $group = $assignmentsByShift->get($shiftId);
                $assignment = $group instanceof Collection ? $group->sortByDesc('id')->first() : null;
            }

            $assignmentId = (int) data_get($assignment, 'id', 0);
            $dayEntryGroup = $assignmentId > 0 ? $dayEntriesByAssignment->get($assignmentId) : null;
            $dayEntry = $dayEntryGroup instanceof Collection ? $dayEntryGroup->first() : null;

            $operatorId = $saleOperatorId
                ?: (int) data_get($assignment, 'pump_operator_id', 0)
                ?: (int) data_get($dayEntry, 'pump_operator_id', 0)
                ?: (int) data_get($shifts->get($shiftId), 'pump_operator_id', 0);
            $resolvedPumpId = $salePumpId
                ?: (int) data_get($assignment, 'pump_id', 0)
                ?: (int) data_get($dayEntry, 'pump_id', 0);

            if ($strictOperator && $pumpOperatorId > 0 && $operatorId !== $pumpOperatorId) {
                continue;
            }
            if ($pumpId > 0 && $resolvedPumpId !== $pumpId) {
                continue;
            }

            if ($dayEntry) {
                $representedDayEntryIds[(int) data_get($dayEntry, 'id', 0)] = true;
            }
            if ($assignmentId > 0) {
                $representedAssignmentIds[$assignmentId] = true;
            }
            $representedMeterSaleIds[$saleId] = true;

            $rows->push($this->sourceRow(
                $businessId,
                $shiftId,
                $operatorId,
                $resolvedPumpId,
                $assignment,
                $dayEntry,
                $sale,
                null,
                $operators,
                $pumps,
                $locations,
                $shifts,
                $settlements
            ));
        }

        foreach ($dayEntries as $dayEntry) {
            $entryId = (int) data_get($dayEntry, 'id', 0);
            $assignmentId = (int) data_get($dayEntry, 'pumper_assignment_id', 0);
            if (isset($representedDayEntryIds[$entryId])
                || ($assignmentId > 0 && isset($representedAssignmentIds[$assignmentId]))) {
                continue;
            }

            $assignment = $assignmentsById->get($assignmentId);
            $directShiftId = (int) data_get($dayEntry, 'shift_id', 0);
            $assignmentShiftId = (int) data_get($assignment, 'shift_id', 0);
            $shiftId = in_array($directShiftId, $shiftIds, true)
                ? $directShiftId
                : (in_array($assignmentShiftId, $shiftIds, true)
                    ? $assignmentShiftId
                    : ($directShiftId ?: $assignmentShiftId));

            if (! in_array($shiftId, $shiftIds, true)) {
                continue;
            }

            $operatorId = (int) data_get($assignment, 'pump_operator_id', 0)
                ?: (int) data_get($dayEntry, 'pump_operator_id', 0)
                ?: (int) data_get($shifts->get($shiftId), 'pump_operator_id', 0);
            if ($strictOperator && $pumpOperatorId > 0 && $operatorId !== $pumpOperatorId) {
                continue;
            }

            $resolvedPumpId = (int) data_get($dayEntry, 'pump_id', 0)
                ?: (int) data_get($assignment, 'pump_id', 0);
            if ($pumpId > 0 && $resolvedPumpId !== $pumpId) {
                continue;
            }

            if ($assignmentId > 0) {
                $representedAssignmentIds[$assignmentId] = true;
            }

            $rows->push($this->sourceRow(
                $businessId,
                $shiftId,
                $operatorId,
                $resolvedPumpId,
                $assignment,
                $dayEntry,
                null,
                null,
                $operators,
                $pumps,
                $locations,
                $shifts,
                $settlements
            ));
        }

        /*
         * Final safety net: a closed pump assignment is itself an operational
         * row.  If a legacy tenant never created a day-entry/detail child, show
         * the assignment meters rather than returning an empty report.
         */
        foreach ($assignments as $assignment) {
            $assignmentId = (int) data_get($assignment, 'id', 0);
            if ($assignmentId <= 0 || isset($representedAssignmentIds[$assignmentId])) {
                continue;
            }

            $shiftId = (int) data_get($assignment, 'shift_id', 0);
            $operatorId = (int) data_get($assignment, 'pump_operator_id', 0);
            $resolvedPumpId = (int) data_get($assignment, 'pump_id', 0);
            if (! in_array($shiftId, $shiftIds, true)) {
                continue;
            }
            if ($strictOperator && $pumpOperatorId > 0 && $operatorId !== $pumpOperatorId) {
                continue;
            }
            if ($pumpId > 0 && $resolvedPumpId !== $pumpId) {
                continue;
            }

            $rows->push($this->sourceRow(
                $businessId,
                $shiftId,
                $operatorId,
                $resolvedPumpId,
                $assignment,
                null,
                null,
                null,
                $operators,
                $pumps,
                $locations,
                $shifts,
                $settlements
            ));
        }

        return $rows
            ->sortBy(static function ($row) {
                $date = (string) data_get($row, 'created_at', data_get($row, 'pde_date', ''));
                $stamp = strtotime($date);
                return sprintf('%020d-%012d', $stamp === false ? 0 : $stamp, (int) data_get($row, 'id', 0));
            })
            ->values();
    }

    private function loadAssignmentsForShifts(int $businessId, array $shiftIds): Collection
    {
        if (! Schema::hasTable('pump_operator_assignments')
            || ! Schema::hasColumn('pump_operator_assignments', 'shift_id')) {
            return collect();
        }

        $query = DB::table('pump_operator_assignments')->whereIn('shift_id', $shiftIds);
        $this->whereBusiness($query, 'pump_operator_assignments', $businessId);

        return $query->get();
    }

    private function loadDayEntries(
        int $businessId,
        array $shiftIds,
        array $assignmentIds,
        int $pumpId
    ): Collection {
        if (! Schema::hasTable('pumper_day_entries')) {
            return collect();
        }

        $hasDirectShift = Schema::hasColumn('pumper_day_entries', 'shift_id');
        $hasAssignment = Schema::hasColumn('pumper_day_entries', 'pumper_assignment_id');
        if (! $hasDirectShift && (! $hasAssignment || empty($assignmentIds))) {
            return collect();
        }

        /*
         * Use the same relationship that already powers the working Close Shift
         * summary: a day entry belongs to the business/shift either directly or
         * through its immutable pump assignment. Some copied tenant databases
         * contain a blank/stale pumper_day_entries.business_id or shift_id while
         * the assignment is correct; filtering the day-entry table alone hides
         * those valid rows.
         */
        $query = DB::table('pumper_day_entries as pde');
        $joinedAssignments = $hasAssignment
            && Schema::hasTable('pump_operator_assignments')
            && Schema::hasColumn('pump_operator_assignments', 'id');

        if ($joinedAssignments) {
            $query->leftJoin(
                'pump_operator_assignments as poa_report',
                'poa_report.id',
                '=',
                'pde.pumper_assignment_id'
            );
        }

        $query->where(function ($businessScope) use ($businessId, $joinedAssignments) {
            $hasDirectBusiness = Schema::hasColumn('pumper_day_entries', 'business_id');
            $hasAssignmentBusiness = $joinedAssignments
                && Schema::hasColumn('pump_operator_assignments', 'business_id');

            if ($hasDirectBusiness) {
                $businessScope->where('pde.business_id', $businessId);
            }
            if ($hasAssignmentBusiness) {
                $method = $hasDirectBusiness ? 'orWhere' : 'where';
                $businessScope->{$method}('poa_report.business_id', $businessId);
            }
            if (! $hasDirectBusiness && ! $hasAssignmentBusiness) {
                $businessScope->whereRaw('1 = 0');
            }
        });

        $query->where(function ($scope) use (
            $hasDirectShift,
            $hasAssignment,
            $joinedAssignments,
            $shiftIds,
            $assignmentIds
        ) {
            $hasCondition = false;
            if ($hasDirectShift) {
                $scope->whereIn('pde.shift_id', $shiftIds);
                $hasCondition = true;
            }
            if ($joinedAssignments && Schema::hasColumn('pump_operator_assignments', 'shift_id')) {
                $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                $scope->{$method}('poa_report.shift_id', $shiftIds);
                $hasCondition = true;
            }
            if ($hasAssignment && ! empty($assignmentIds)) {
                $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                $scope->{$method}('pde.pumper_assignment_id', $assignmentIds);
            }
        });

        if ($pumpId > 0 && Schema::hasColumn('pumper_day_entries', 'pump_id')) {
            $query->where('pde.pump_id', $pumpId);
        }

        return $query->select('pde.*')->get();
    }

    private function loadMeterSales(int $businessId, array $shiftIds): Collection
    {
        if (! Schema::hasTable('pump_operator_meter_sales')
            || ! Schema::hasColumn('pump_operator_meter_sales', 'shift_id')) {
            return collect();
        }

        $query = DB::table('pump_operator_meter_sales')->whereIn('shift_id', $shiftIds);
        $this->whereBusiness($query, 'pump_operator_meter_sales', $businessId);
        $allRows = $query->get();

        if (! Schema::hasColumn('pump_operator_meter_sales', 'source')) {
            return $allRows;
        }

        // Prefer the normal operational rows. If a copied/legacy tenant has only
        // rows marked as "payment", keep them as a last-resort operational source
        // instead of showing an empty report while the shift summary has totals.
        $operationalRows = $allRows->filter(static function ($row) {
            $source = strtolower(trim((string) data_get($row, 'source', '')));

            return $source === '' || $source !== 'payment';
        })->values();

        return $operationalRows->isNotEmpty() ? $operationalRows : $allRows;
    }

    private function loadMeterSaleDetails(
        int $businessId,
        array $saleIds,
        int $pumpId
    ): Collection {
        if (empty($saleIds)
            || ! Schema::hasTable('pump_operator_meter_sale_details')
            || ! Schema::hasColumn('pump_operator_meter_sale_details', 'sale_id')) {
            return collect();
        }

        $query = DB::table('pump_operator_meter_sale_details')->whereIn('sale_id', $saleIds);
        $this->whereBusiness($query, 'pump_operator_meter_sale_details', $businessId);
        if ($pumpId > 0 && Schema::hasColumn('pump_operator_meter_sale_details', 'pump_id')) {
            $query->where('pump_id', $pumpId);
        }

        return $query->get();
    }

    private function loadSettlements(Collection $assignments): Collection
    {
        if (! Schema::hasTable('settlements')) {
            return collect();
        }

        $ids = $assignments->pluck('settlement_id')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return empty($ids)
            ? collect()
            : DB::table('settlements')->whereIn('id', $ids)->get()->keyBy('id');
    }

    private function loadShifts(int $businessId, array $shiftIds): Collection
    {
        if (! Schema::hasTable('petro_shifts')) {
            return collect();
        }

        $query = DB::table('petro_shifts')->whereIn('id', $shiftIds);
        $this->whereBusiness($query, 'petro_shifts', $businessId);

        return $query->get()->keyBy('id');
    }

    private function loadOperators(int $businessId, array $operatorIds): Collection
    {
        if (empty($operatorIds) || ! Schema::hasTable('pump_operators')) {
            return collect();
        }

        $query = DB::table('pump_operators')->whereIn('id', $operatorIds);
        $this->whereBusiness($query, 'pump_operators', $businessId);

        return $query->get()->keyBy('id');
    }

    private function loadPumps(int $businessId, array $pumpIds): Collection
    {
        if (empty($pumpIds) || ! Schema::hasTable('pumps')) {
            return collect();
        }

        $query = DB::table('pumps')->whereIn('id', $pumpIds);
        $this->whereBusiness($query, 'pumps', $businessId);

        return $query->get()->keyBy('id');
    }

    private function loadLocations(int $businessId, array $locationIds): Collection
    {
        if (empty($locationIds) || ! Schema::hasTable('business_locations')) {
            return collect();
        }

        $query = DB::table('business_locations')->whereIn('id', $locationIds);
        $this->whereBusiness($query, 'business_locations', $businessId);

        return $query->get()->keyBy('id');
    }

    private function sourceRow(
        int $businessId,
        int $shiftId,
        int $operatorId,
        int $pumpId,
        $assignment,
        $dayEntry,
        $meterSale,
        $detail,
        Collection $operators,
        Collection $pumps,
        Collection $locations,
        Collection $shifts,
        Collection $settlements
    ): object {
        $operator = $operators->get($operatorId);
        $pump = $pumps->get($pumpId);
        $shift = $shifts->get($shiftId);
        $settlement = $settlements->get((int) data_get($assignment, 'settlement_id', 0));
        $assignmentId = (int) data_get($assignment, 'id', 0);

        $createdAt = data_get($dayEntry, 'created_at')
            ?? data_get($detail, 'created_at')
            ?? data_get($meterSale, 'date_time')
            ?? data_get($meterSale, 'created_at')
            ?? data_get($assignment, 'date_and_time')
            ?? data_get($assignment, 'created_at');
        $date = data_get($dayEntry, 'date')
            ?? data_get($meterSale, 'transaction_date')
            ?? $createdAt;
        $time = data_get($dayEntry, 'time')
            ?? data_get($meterSale, 'date_time')
            ?? $createdAt;

        $pumpNo = trim((string) data_get($dayEntry, 'pump_no', ''));
        if ($pumpNo === '') {
            $pumpNo = trim((string) data_get($pump, 'pump_name', ''));
        }
        if ($pumpNo === '') {
            $pumpNo = trim((string) data_get($pump, 'pump_no', ''));
        }
        if ($pumpNo === '' && $pumpId > 0) {
            $pumpNo = 'Pump #' . $pumpId;
        }

        $locationId = (int) data_get($operator, 'location_id', 0);
        $settlementNo = data_get($settlement, 'settlement_no')
            ?? data_get($dayEntry, 'settlement_no');
        $shiftNumber = data_get($assignment, 'shift_number')
            ?? data_get($shift, 'shift_number')
            ?? data_get($shift, 'shift_no')
            ?? $shiftId;

        $sourceType = $dayEntry
            ? 'day_entry'
            : ($detail ? 'meter_detail' : ($meterSale ? 'meter_sale' : 'assignment'));

        return (object) [
            'source_type' => $sourceType,
            'entry_id' => data_get($dayEntry, 'id'),
            'detail_id' => data_get($detail, 'id'),
            'sale_id' => data_get($meterSale, 'id'),
            'id' => data_get($dayEntry, 'id') ?? data_get($detail, 'id') ?? data_get($meterSale, 'id') ?? ($assignmentId ?: null),
            'business_id' => $businessId,
            'pump_operator_id' => $operatorId,
            'shift_id' => $shiftId,
            'pump_operator_name' => (string) data_get($operator, 'name', 'Pump Operator'),
            'created_at' => $createdAt,
            'updated_at' => data_get($dayEntry, 'updated_at') ?? data_get($detail, 'updated_at') ?? $createdAt,
            'pde_date' => $date,
            'date' => $date,
            'time' => $time,
            'settlement_datetime' => data_get($dayEntry, 'settlement_datetime'),
            'settlement_transaction_date' => data_get($settlement, 'transaction_date'),
            'pump_id' => $pumpId,
            'starting_meter' => data_get($detail, 'received_meter')
                ?? data_get($meterSale, 'starting_meter')
                ?? data_get($meterSale, 'received_meter')
                ?? data_get($dayEntry, 'starting_meter')
                ?? data_get($assignment, 'starting_meter')
                ?? 0,
            'closing_meter' => data_get($detail, 'new_meter')
                ?? data_get($meterSale, 'closing_meter')
                ?? data_get($meterSale, 'new_meter')
                ?? data_get($dayEntry, 'closing_meter')
                ?? data_get($assignment, 'closing_meter')
                ?? 0,
            'sold_qty' => data_get($detail, 'sold_qty')
                ?? data_get($meterSale, 'sold_qty')
                ?? data_get($meterSale, 'sold_ltr')
                ?? data_get($meterSale, 'qty')
                ?? data_get($dayEntry, 'sold_ltr')
                ?? data_get($assignment, 'sold_ltr')
                ?? data_get($assignment, 'sold_qty')
                ?? 0,
            'sold_ltr' => data_get($detail, 'sold_qty')
                ?? data_get($meterSale, 'sold_qty')
                ?? data_get($meterSale, 'sold_ltr')
                ?? data_get($meterSale, 'qty')
                ?? data_get($dayEntry, 'sold_ltr')
                ?? data_get($assignment, 'sold_ltr')
                ?? data_get($assignment, 'sold_qty')
                ?? 0,
            'amount' => data_get($detail, 'amount')
                ?? data_get($meterSale, 'amount')
                ?? data_get($dayEntry, 'amount')
                ?? data_get($assignment, 'amount')
                ?? 0,
            'testing_ltr' => data_get($dayEntry, 'testing_ltr')
                ?? data_get($meterSale, 'testing_qty')
                ?? data_get($meterSale, 'testing_ltr')
                ?? data_get($assignment, 'testing_ltr')
                ?? data_get($assignment, 'testing_qty')
                ?? 0,
            'settlement_no' => $settlementNo,
            'assignment_id' => $assignmentId ?: null,
            'assignment_status' => data_get($assignment, 'status'),
            'is_confirmed' => data_get($assignment, 'is_confirmed'),
            'shift_status' => data_get($shift, 'status'),
            'shift_number' => $shiftNumber,
            'pde_pump_id' => (int) data_get($dayEntry, 'pump_id', 0) ?: $pumpId,
            'pumper_assignment_id' => (int) data_get($dayEntry, 'pumper_assignment_id', 0) ?: ($assignmentId ?: null),
            'pump_no' => $pumpNo,
            'location_name' => (string) data_get($locations->get($locationId), 'name', ''),
            'location_id' => $locationId,
        ];
    }

    private function safeSource(
        callable $loader,
        string $source,
        int $businessId,
        array $shiftIds
    ): Collection {
        try {
            $result = $loader();

            return $result instanceof Collection ? $result : collect($result);
        } catch (\Throwable $e) {
            Log::warning('PetroPD operator report source could not be loaded', [
                'source' => $source,
                'business_id' => $businessId,
                'shift_ids' => $shiftIds,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    private function whereBusiness($query, string $table, int $businessId): void
    {
        if (Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        }
    }

    private function tuple(int $shiftId, int $operatorId, int $pumpId): string
    {
        return $shiftId . ':' . $operatorId . ':' . $pumpId;
    }
}
