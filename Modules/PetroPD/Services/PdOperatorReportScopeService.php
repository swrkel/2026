<?php

namespace Modules\PetroPD\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the authoritative shift/operator scope for Petro PD operator reports.
 *
 * Live tenant databases do not always keep the same relationship populated:
 * newer rows can carry shift_id directly while older rows are connected through
 * pump_operator_assignments.  This service starts from actual operational data
 * and merges every supported relationship so Close Shift and Pumper Day Entries
 * always use the same business/date/operator/shift scope.
 */
class PdOperatorReportScopeService
{
    public function resolveDateRange(Request $request, bool $defaultToToday = true): array
    {
        $startDate = trim((string) $request->input('start_date', ''));
        $endDate = trim((string) $request->input('end_date', ''));

        if ($startDate === '' && $defaultToToday) {
            $startDate = Carbon::today()->toDateString();
        }
        if ($endDate === '' && $defaultToToday) {
            $endDate = Carbon::today()->toDateString();
        }

        try {
            $startDate = $startDate !== '' ? Carbon::parse($startDate)->toDateString() : '';
        } catch (\Throwable $e) {
            $startDate = $defaultToToday ? Carbon::today()->toDateString() : '';
        }

        try {
            $endDate = $endDate !== '' ? Carbon::parse($endDate)->toDateString() : '';
        } catch (\Throwable $e) {
            $endDate = $defaultToToday ? Carbon::today()->toDateString() : '';
        }

        if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    /**
     * Return normalized shift rows for the requested report scope.
     */
    public function shifts(
        int $businessId,
        string $startDate = '',
        string $endDate = '',
        int $pumpOperatorId = 0,
        bool $activeOperatorsOnly = true,
        int $selectedShiftId = 0
    ): Collection {
        // A specifically selected Shift No is authoritative. Historical rows can
        // carry a stale operator id on one source table, so validate the shift
        // against the business only and resolve its operator from all available
        // relationships below. Operator filtering remains strict for the All
        // Shifts view.
        $rowOperatorId = $selectedShiftId > 0 ? 0 : $pumpOperatorId;
        $rowActiveOperatorsOnly = $selectedShiftId > 0 ? false : $activeOperatorsOnly;
        $shiftIds = $selectedShiftId > 0
            ? $this->validateSelectedShift($businessId, $selectedShiftId, 0)
            : $this->activityShiftIds($businessId, $startDate, $endDate, $pumpOperatorId);

        if (empty($shiftIds)) {
            return collect();
        }

        // A tenant can contain an older version of one optional source table.
        // Load every source independently so one missing legacy column cannot
        // turn a valid selected shift into an empty Ajax response.
        $rows = collect()
            ->concat($this->safeRows(
                fn () => $this->assignmentShiftRows(
                    $businessId,
                    $shiftIds,
                    $rowOperatorId,
                    $rowActiveOperatorsOnly
                ),
                'pump_operator_assignments',
                $businessId,
                $shiftIds
            ))
            ->concat($this->safeRows(
                fn () => $this->directPetroShiftRows(
                    $businessId,
                    $shiftIds,
                    $rowOperatorId,
                    $rowActiveOperatorsOnly
                ),
                'petro_shifts',
                $businessId,
                $shiftIds
            ))
            ->concat($this->safeRows(
                fn () => $this->directDayEntryShiftRows(
                    $businessId,
                    $shiftIds,
                    $rowOperatorId,
                    $rowActiveOperatorsOnly
                ),
                'pumper_day_entries',
                $businessId,
                $shiftIds
            ))
            ->concat($this->safeRows(
                fn () => $this->meterSaleShiftRows(
                    $businessId,
                    $shiftIds,
                    $rowOperatorId,
                    $rowActiveOperatorsOnly
                ),
                'pump_operator_meter_sales',
                $businessId,
                $shiftIds
            ))
            ->concat($this->safeRows(
                fn () => $this->paymentShiftRows(
                    $businessId,
                    $shiftIds,
                    $rowOperatorId,
                    $rowActiveOperatorsOnly
                ),
                'pump_operator_payments',
                $businessId,
                $shiftIds
            ));

        if ($selectedShiftId > 0 && $rows->isEmpty()) {
            $fallback = $this->safeRows(
                fn () => collect([$this->selectedShiftFallbackRow(
                    $businessId,
                    $selectedShiftId,
                    $pumpOperatorId
                )]),
                'selected_shift_fallback',
                $businessId,
                [$selectedShiftId]
            )->first();

            if ($fallback) {
                $rows->push($fallback);
            }
        }

        $rows = $rows
            ->filter(static function ($row) use ($selectedShiftId) {
                return (int) ($row->id ?? 0) > 0
                    && ($selectedShiftId > 0 || (int) ($row->pump_operator_id ?? 0) > 0);
            });

        // Keep one deterministic metadata row per shift. Assignment-linked
        // metadata has the highest priority; when a selected operator was
        // supplied, prefer that operator inside the selected shift as well.
        return $rows
            ->groupBy(static fn ($row) => (int) $row->id)
            ->map(function (Collection $group) use ($pumpOperatorId) {
                return $group->sortByDesc(static function ($row) use ($pumpOperatorId) {
                    $operatorMatch = $pumpOperatorId > 0
                        && (int) ($row->pump_operator_id ?? 0) === $pumpOperatorId
                            ? 1
                            : 0;
                    $priority = (int) ($row->source_priority ?? 0);
                    $rowId = (int) ($row->source_row_id ?? 0);

                    return sprintf('%02d-%03d-%012d', $operatorMatch, $priority, $rowId);
                })->first();
            })
            ->sortByDesc(static function ($row) {
                $date = trim((string) ($row->filter_date ?? ''));
                $stamp = $date !== '' ? strtotime($date) : false;
                $stamp = $stamp === false ? 0 : $stamp;
                $number = (int) ($row->display_shift_number ?? $row->id ?? 0);

                return sprintf('%020d-%012d', $stamp, $number);
            })
            ->values();
    }

    /**
     * Collect shift IDs which actually contain operational activity during the
     * requested date range.  No single table/date column is trusted alone.
     */
    private function activityShiftIds(
        int $businessId,
        string $startDate,
        string $endDate,
        int $pumpOperatorId
    ): array {
        $ids = collect();

        if (Schema::hasTable('pump_operator_assignments')) {
            $query = DB::table('pump_operator_assignments as poa')
                ->where('poa.business_id', $businessId)
                ->whereNotNull('poa.shift_id')
                ->where('poa.shift_id', '>', 0);

            if ($pumpOperatorId > 0) {
                $query->where('poa.pump_operator_id', $pumpOperatorId);
            }

            $this->applyDateRange(
                $query,
                $this->dateExpression(
                    'pump_operator_assignments',
                    'poa',
                    ['date_and_time', 'transaction_date', 'date', 'created_at']
                ),
                $startDate,
                $endDate
            );

            $ids = $ids->concat($query->pluck('poa.shift_id'));
        }

        if (Schema::hasTable('pumper_day_entries')) {
            $hasAssignment = Schema::hasTable('pump_operator_assignments')
                && Schema::hasColumn('pumper_day_entries', 'pumper_assignment_id');
            $hasDirectShift = Schema::hasColumn('pumper_day_entries', 'shift_id');

            $query = DB::table('pumper_day_entries as pde');
            if ($hasAssignment) {
                $query->leftJoin(
                    'pump_operator_assignments as poa_pde',
                    'poa_pde.id',
                    '=',
                    'pde.pumper_assignment_id'
                );
            }

            $query->where(function ($businessQuery) use ($businessId, $hasAssignment) {
                if (Schema::hasColumn('pumper_day_entries', 'business_id')) {
                    $businessQuery->where('pde.business_id', $businessId);
                    if ($hasAssignment) {
                        $businessQuery->orWhere('poa_pde.business_id', $businessId);
                    }
                } elseif ($hasAssignment) {
                    $businessQuery->where('poa_pde.business_id', $businessId);
                } else {
                    $businessQuery->whereRaw('1 = 0');
                }
            });

            if ($pumpOperatorId > 0) {
                $query->where(function ($operatorQuery) use ($pumpOperatorId, $hasAssignment) {
                    if ($hasAssignment) {
                        $operatorQuery->where('poa_pde.pump_operator_id', $pumpOperatorId)
                            ->orWhere('pde.pump_operator_id', $pumpOperatorId);
                    } else {
                        $operatorQuery->where('pde.pump_operator_id', $pumpOperatorId);
                    }
                });
            }

            $dateColumns = ['date', 'date_and_time', 'settlement_datetime', 'created_at'];
            $dateExpression = $this->dateExpression('pumper_day_entries', 'pde', $dateColumns);
            if ($hasAssignment) {
                $assignmentDate = $this->dateExpression(
                    'pump_operator_assignments',
                    'poa_pde',
                    ['date_and_time', 'transaction_date', 'date', 'created_at']
                );
                $dateExpression = $this->coalesceExpressions([$dateExpression, $assignmentDate]);
            }

            $this->applyDateRange($query, $dateExpression, $startDate, $endDate);

            // Keep both relationships. Some historical records contain a
            // corrected direct shift_id while their old assignment still points
            // to another shift (or the reverse). COALESCE would hide one side.
            if ($hasAssignment) {
                $assignmentQuery = clone $query;
                $ids = $ids->concat(
                    $assignmentQuery
                        ->whereNotNull('poa_pde.shift_id')
                        ->where('poa_pde.shift_id', '>', 0)
                        ->pluck('poa_pde.shift_id')
                );
            }
            if ($hasDirectShift) {
                $directQuery = clone $query;
                $ids = $ids->concat(
                    $directQuery
                        ->whereNotNull('pde.shift_id')
                        ->where('pde.shift_id', '>', 0)
                        ->pluck('pde.shift_id')
                );
            }
        }

        if (Schema::hasTable('pump_operator_meter_sales')) {
            $query = DB::table('pump_operator_meter_sales as poms')
                ->where('poms.business_id', $businessId)
                ->whereNotNull('poms.shift_id')
                ->where('poms.shift_id', '>', 0);

            if ($pumpOperatorId > 0) {
                $query->where('poms.pump_operator_id', $pumpOperatorId);
            }

            $this->applyDateRange(
                $query,
                $this->dateExpression(
                    'pump_operator_meter_sales',
                    'poms',
                    ['transaction_date', 'date', 'date_and_time', 'created_at']
                ),
                $startDate,
                $endDate
            );

            $ids = $ids->concat($query->pluck('poms.shift_id'));
        }

        if (Schema::hasTable('pump_operator_payments')) {
            $query = DB::table('pump_operator_payments as pop')
                ->where('pop.business_id', $businessId)
                ->whereNotNull('pop.shift_id')
                ->where('pop.shift_id', '>', 0);

            if ($pumpOperatorId > 0) {
                $query->where('pop.pump_operator_id', $pumpOperatorId);
            }

            $this->applyDateRange(
                $query,
                $this->dateExpression(
                    'pump_operator_payments',
                    'pop',
                    ['transaction_date', 'date', 'date_and_time', 'created_at']
                ),
                $startDate,
                $endDate
            );

            $ids = $ids->concat($query->pluck('pop.shift_id'));
        }

        if (Schema::hasTable('pump_operator_other_sales')) {
            $hasAssignment = Schema::hasTable('pump_operator_assignments');
            $hasOperator = Schema::hasColumn('pump_operator_other_sales', 'pump_operator_id');
            $query = DB::table('pump_operator_other_sales as poos')
                ->where('poos.business_id', $businessId)
                ->whereNotNull('poos.shift_id')
                ->where('poos.shift_id', '>', 0);

            if ($pumpOperatorId > 0) {
                $query->where(function ($operatorQuery) use (
                    $hasOperator,
                    $hasAssignment,
                    $businessId,
                    $pumpOperatorId
                ) {
                    if ($hasOperator) {
                        $operatorQuery->where('poos.pump_operator_id', $pumpOperatorId);
                    }
                    if ($hasAssignment) {
                        $method = $hasOperator ? 'orWhereExists' : 'whereExists';
                        $operatorQuery->{$method}(function ($subQuery) use ($businessId, $pumpOperatorId) {
                            $subQuery->selectRaw('1')
                                ->from('pump_operator_assignments as poa_poos')
                                ->whereColumn('poa_poos.shift_id', 'poos.shift_id')
                                ->where('poa_poos.business_id', $businessId)
                                ->where('poa_poos.pump_operator_id', $pumpOperatorId);
                        });
                    }
                    if (! $hasOperator && ! $hasAssignment) {
                        $operatorQuery->whereRaw('1 = 0');
                    }
                });
            }

            $this->applyDateRange(
                $query,
                $this->dateExpression(
                    'pump_operator_other_sales',
                    'poos',
                    ['transaction_date', 'date', 'date_and_time', 'created_at']
                ),
                $startDate,
                $endDate
            );

            $ids = $ids->concat($query->pluck('poos.shift_id'));
        }

        if (Schema::hasTable('petro_shifts')) {
            $query = DB::table('petro_shifts as ps')
                ->where('ps.business_id', $businessId);

            if ($pumpOperatorId > 0 && Schema::hasColumn('petro_shifts', 'pump_operator_id')) {
                $query->where('ps.pump_operator_id', $pumpOperatorId);
            }

            $this->applyDateRange(
                $query,
                $this->dateExpression(
                    'petro_shifts',
                    'ps',
                    ['transaction_date', 'shift_date', 'date', 'start_date_and_time', 'date_and_time', 'created_at']
                ),
                $startDate,
                $endDate
            );

            $ids = $ids->concat($query->pluck('ps.id'));
        }

        return $ids
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Last-resort metadata for an explicitly selected, business-owned shift.
     * The data tables only need the immutable shift ID to load their rows; a
     * missing legacy operator relationship must never turn that valid shift
     * into an empty report.
     */
    private function selectedShiftFallbackRow(
        int $businessId,
        int $shiftId,
        int $requestedOperatorId
    ): object {
        $operatorId = $requestedOperatorId;
        $shiftNumber = $shiftId;
        $status = '';
        $filterDate = null;

        if (Schema::hasTable('pump_operator_assignments')) {
            $assignment = DB::table('pump_operator_assignments')
                ->when(Schema::hasColumn('pump_operator_assignments', 'business_id'), function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                })
                ->where('shift_id', $shiftId)
                ->when($requestedOperatorId > 0, function ($query) use ($requestedOperatorId) {
                    $query->orderByRaw('CASE WHEN pump_operator_id = ? THEN 0 ELSE 1 END', [$requestedOperatorId]);
                })
                ->orderByDesc('id')
                ->first();

            if ($assignment) {
                $operatorId = $operatorId ?: (int) ($assignment->pump_operator_id ?? 0);
                $shiftNumber = (int) ($assignment->shift_number ?? 0) ?: $shiftNumber;
                $status = (string) ($assignment->status ?? '');
                $filterDate = data_get($assignment, 'date_and_time')
                    ?? data_get($assignment, 'transaction_date')
                    ?? data_get($assignment, 'date')
                    ?? data_get($assignment, 'created_at');
            }
        }

        if (Schema::hasTable('petro_shifts')) {
            $shift = DB::table('petro_shifts')
                ->when(Schema::hasColumn('petro_shifts', 'business_id'), function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                })
                ->where('id', $shiftId)
                ->first();

            if ($shift) {
                $operatorId = $operatorId ?: (int) ($shift->pump_operator_id ?? 0);
                $shiftNumber = (int) ($shift->shift_number ?? $shift->shift_no ?? 0) ?: $shiftNumber;
                $status = (string) ($shift->status ?? $status);
                $filterDate = $filterDate
                    ?? data_get($shift, 'transaction_date')
                    ?? data_get($shift, 'shift_date')
                    ?? data_get($shift, 'date')
                    ?? data_get($shift, 'start_date_and_time')
                    ?? data_get($shift, 'date_and_time')
                    ?? data_get($shift, 'created_at');
            }
        }

        foreach (['pumper_day_entries', 'pump_operator_meter_sales', 'pump_operator_payments'] as $table) {
            if ($operatorId > 0 || ! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'shift_id')) {
                continue;
            }

            $query = DB::table($table)->where('shift_id', $shiftId);
            if (Schema::hasColumn($table, 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if (Schema::hasColumn($table, 'pump_operator_id')) {
                $operatorId = (int) ($query->orderByDesc('id')->value('pump_operator_id') ?? 0);
            }
        }

        $operatorName = '';
        if ($operatorId > 0 && Schema::hasTable('pump_operators')) {
            $operatorQuery = DB::table('pump_operators')->where('id', $operatorId);
            if (Schema::hasColumn('pump_operators', 'business_id')) {
                $operatorQuery->where('business_id', $businessId);
            }
            $operatorName = (string) ($operatorQuery->value('name') ?? '');
        }

        return (object) [
            'id' => $shiftId,
            'pump_operator_id' => $operatorId,
            'operator_name' => $operatorName !== '' ? $operatorName : 'Pump Operator',
            'display_shift_number' => $shiftNumber,
            'status' => $status,
            'filter_date' => $filterDate,
            'source_row_id' => $shiftId,
            'source_priority' => 1,
        ];
    }

    private function validateSelectedShift(
        int $businessId,
        int $selectedShiftId,
        int $pumpOperatorId
    ): array {
        $belongs = false;

        if (Schema::hasTable('pump_operator_assignments')
            && Schema::hasColumn('pump_operator_assignments', 'shift_id')) {
            $query = DB::table('pump_operator_assignments')
                ->where('shift_id', $selectedShiftId);
            if (Schema::hasColumn('pump_operator_assignments', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if ($pumpOperatorId > 0
                && Schema::hasColumn('pump_operator_assignments', 'pump_operator_id')) {
                $query->where('pump_operator_id', $pumpOperatorId);
            }
            $belongs = $query->exists();
        }

        if (! $belongs && Schema::hasTable('petro_shifts')) {
            $query = DB::table('petro_shifts')->where('id', $selectedShiftId);
            if (Schema::hasColumn('petro_shifts', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if ($pumpOperatorId > 0
                && Schema::hasColumn('petro_shifts', 'pump_operator_id')) {
                $query->where('pump_operator_id', $pumpOperatorId);
            }
            $belongs = $query->exists();
        }

        if (! $belongs && Schema::hasTable('pumper_day_entries')
            && Schema::hasColumn('pumper_day_entries', 'shift_id')) {
            $query = DB::table('pumper_day_entries')->where('shift_id', $selectedShiftId);
            if (Schema::hasColumn('pumper_day_entries', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if ($pumpOperatorId > 0
                && Schema::hasColumn('pumper_day_entries', 'pump_operator_id')) {
                $query->where('pump_operator_id', $pumpOperatorId);
            }
            $belongs = $query->exists();
        }

        foreach (['pump_operator_meter_sales', 'pump_operator_payments'] as $table) {
            if ($belongs || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'shift_id')) {
                continue;
            }

            $query = DB::table($table)->where('shift_id', $selectedShiftId);
            if (Schema::hasColumn($table, 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if ($pumpOperatorId > 0 && Schema::hasColumn($table, 'pump_operator_id')) {
                $query->where('pump_operator_id', $pumpOperatorId);
            }
            $belongs = $query->exists();
        }

        if (! $belongs && Schema::hasTable('pump_operator_other_sales')
            && Schema::hasColumn('pump_operator_other_sales', 'shift_id')) {
            $query = DB::table('pump_operator_other_sales')
                ->where('shift_id', $selectedShiftId);
            if (Schema::hasColumn('pump_operator_other_sales', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            $belongs = $query->exists();
        }

        return $belongs ? [$selectedShiftId] : [];
    }


    /**
     * Keep report scope discovery resilient across copied tenant databases.
     */
    private function safeRows(
        callable $loader,
        string $source,
        int $businessId,
        array $shiftIds
    ): Collection {
        try {
            $rows = $loader();

            return $rows instanceof Collection ? $rows : collect($rows);
        } catch (\Throwable $e) {
            Log::warning('PetroPD report scope source could not be loaded', [
                'source' => $source,
                'business_id' => $businessId,
                'shift_ids' => $shiftIds,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    private function assignmentShiftRows(
        int $businessId,
        array $shiftIds,
        int $pumpOperatorId,
        bool $activeOperatorsOnly
    ): Collection {
        if (! Schema::hasTable('pump_operator_assignments')
            || ! Schema::hasTable('pump_operators')
            || ! Schema::hasTable('petro_shifts')) {
            return collect();
        }

        $query = DB::table('pump_operator_assignments as poa')
            ->join('pump_operators as po', 'po.id', '=', 'poa.pump_operator_id')
            ->leftJoin('petro_shifts as ps', 'ps.id', '=', 'poa.shift_id')
            ->where('poa.business_id', $businessId)
            ->where('po.business_id', $businessId)
            ->whereIn('poa.shift_id', $shiftIds);

        if ($pumpOperatorId > 0) {
            $query->where('poa.pump_operator_id', $pumpOperatorId);
        }
        if ($activeOperatorsOnly && Schema::hasColumn('pump_operators', 'active')) {
            $query->where('po.active', 1);
        }

        $shiftNumberExpression = $this->coalesceExpressions([
            Schema::hasColumn('pump_operator_assignments', 'shift_number')
                ? 'NULLIF(poa.shift_number, 0)'
                : null,
            Schema::hasColumn('petro_shifts', 'shift_number')
                ? 'NULLIF(ps.shift_number, 0)'
                : null,
            Schema::hasColumn('petro_shifts', 'shift_no')
                ? 'NULLIF(ps.shift_no, 0)'
                : null,
            'poa.shift_id',
        ]);
        $dateExpression = $this->coalesceExpressions([
            $this->dateExpression(
                'pump_operator_assignments',
                'poa',
                ['date_and_time', 'transaction_date', 'date', 'created_at']
            ),
            $this->dateExpression(
                'petro_shifts',
                'ps',
                ['transaction_date', 'shift_date', 'date', 'start_date_and_time', 'date_and_time', 'created_at']
            ),
        ]);
        $statusExpression = Schema::hasColumn('petro_shifts', 'status')
            ? 'ps.status'
            : (Schema::hasColumn('pump_operator_assignments', 'status') ? 'poa.status' : "''");

        return $query
            ->selectRaw('poa.shift_id AS id')
            ->selectRaw('poa.pump_operator_id')
            ->selectRaw('po.name AS operator_name')
            ->selectRaw($shiftNumberExpression . ' AS display_shift_number')
            ->selectRaw($statusExpression . ' AS status')
            ->selectRaw(($dateExpression ?: 'NULL') . ' AS filter_date')
            ->selectRaw('poa.id AS source_row_id')
            ->selectRaw('100 AS source_priority')
            ->orderByDesc('poa.id')
            ->get();
    }

    private function directPetroShiftRows(
        int $businessId,
        array $shiftIds,
        int $pumpOperatorId,
        bool $activeOperatorsOnly
    ): Collection {
        if (! Schema::hasTable('petro_shifts')
            || ! Schema::hasTable('pump_operators')
            || ! Schema::hasColumn('petro_shifts', 'pump_operator_id')) {
            return collect();
        }

        $query = DB::table('petro_shifts as ps')
            ->join('pump_operators as po', 'po.id', '=', 'ps.pump_operator_id')
            ->where('ps.business_id', $businessId)
            ->where('po.business_id', $businessId)
            ->whereIn('ps.id', $shiftIds);

        if ($pumpOperatorId > 0) {
            $query->where('ps.pump_operator_id', $pumpOperatorId);
        }
        if ($activeOperatorsOnly && Schema::hasColumn('pump_operators', 'active')) {
            $query->where('po.active', 1);
        }

        $shiftNumberExpression = $this->coalesceExpressions([
            Schema::hasColumn('petro_shifts', 'shift_number')
                ? 'NULLIF(ps.shift_number, 0)'
                : null,
            Schema::hasColumn('petro_shifts', 'shift_no')
                ? 'NULLIF(ps.shift_no, 0)'
                : null,
            'ps.id',
        ]);
        $dateExpression = $this->dateExpression(
            'petro_shifts',
            'ps',
            ['transaction_date', 'shift_date', 'date', 'start_date_and_time', 'date_and_time', 'created_at']
        );
        $statusExpression = Schema::hasColumn('petro_shifts', 'status') ? 'ps.status' : "''";

        return $query
            ->selectRaw('ps.id')
            ->selectRaw('ps.pump_operator_id')
            ->selectRaw('po.name AS operator_name')
            ->selectRaw($shiftNumberExpression . ' AS display_shift_number')
            ->selectRaw($statusExpression . ' AS status')
            ->selectRaw(($dateExpression ?: 'NULL') . ' AS filter_date')
            ->selectRaw('ps.id AS source_row_id')
            ->selectRaw('80 AS source_priority')
            ->orderByDesc('ps.id')
            ->get();
    }

    private function directDayEntryShiftRows(
        int $businessId,
        array $shiftIds,
        int $pumpOperatorId,
        bool $activeOperatorsOnly
    ): Collection {
        if (! Schema::hasTable('pumper_day_entries')
            || ! Schema::hasColumn('pumper_day_entries', 'shift_id')
            || ! Schema::hasTable('pump_operators')
            || ! Schema::hasTable('petro_shifts')) {
            return collect();
        }

        $query = DB::table('pumper_day_entries as pde')
            ->join('pump_operators as po', 'po.id', '=', 'pde.pump_operator_id')
            ->leftJoin('petro_shifts as ps', 'ps.id', '=', 'pde.shift_id')
            ->where('pde.business_id', $businessId)
            ->where('po.business_id', $businessId)
            ->whereIn('pde.shift_id', $shiftIds);

        if ($pumpOperatorId > 0) {
            $query->where('pde.pump_operator_id', $pumpOperatorId);
        }
        if ($activeOperatorsOnly && Schema::hasColumn('pump_operators', 'active')) {
            $query->where('po.active', 1);
        }

        $dateExpression = $this->dateExpression(
            'pumper_day_entries',
            'pde',
            ['date', 'date_and_time', 'settlement_datetime', 'created_at']
        );
        $statusExpression = Schema::hasColumn('petro_shifts', 'status') ? 'ps.status' : "''";

        return $query
            ->selectRaw('pde.shift_id AS id')
            ->selectRaw('pde.pump_operator_id')
            ->selectRaw('po.name AS operator_name')
            ->selectRaw('pde.shift_id AS display_shift_number')
            ->selectRaw($statusExpression . ' AS status')
            ->selectRaw(($dateExpression ?: 'NULL') . ' AS filter_date')
            ->selectRaw('pde.id AS source_row_id')
            ->selectRaw('60 AS source_priority')
            ->orderByDesc('pde.id')
            ->get();
    }

    private function meterSaleShiftRows(
        int $businessId,
        array $shiftIds,
        int $pumpOperatorId,
        bool $activeOperatorsOnly
    ): Collection {
        if (! Schema::hasTable('pump_operator_meter_sales')
            || ! Schema::hasTable('pump_operators')
            || ! Schema::hasTable('petro_shifts')) {
            return collect();
        }

        $query = DB::table('pump_operator_meter_sales as poms')
            ->join('pump_operators as po', 'po.id', '=', 'poms.pump_operator_id')
            ->leftJoin('petro_shifts as ps', 'ps.id', '=', 'poms.shift_id')
            ->where('poms.business_id', $businessId)
            ->where('po.business_id', $businessId)
            ->whereIn('poms.shift_id', $shiftIds);

        if ($pumpOperatorId > 0) {
            $query->where('poms.pump_operator_id', $pumpOperatorId);
        }
        if ($activeOperatorsOnly && Schema::hasColumn('pump_operators', 'active')) {
            $query->where('po.active', 1);
        }

        $dateExpression = $this->dateExpression(
            'pump_operator_meter_sales',
            'poms',
            ['transaction_date', 'date', 'date_and_time', 'created_at']
        );
        $statusExpression = Schema::hasColumn('petro_shifts', 'status') ? 'ps.status' : "''";

        return $query
            ->selectRaw('poms.shift_id AS id')
            ->selectRaw('poms.pump_operator_id')
            ->selectRaw('po.name AS operator_name')
            ->selectRaw('poms.shift_id AS display_shift_number')
            ->selectRaw($statusExpression . ' AS status')
            ->selectRaw(($dateExpression ?: 'NULL') . ' AS filter_date')
            ->selectRaw('poms.id AS source_row_id')
            ->selectRaw('50 AS source_priority')
            ->orderByDesc('poms.id')
            ->get();
    }

    private function paymentShiftRows(
        int $businessId,
        array $shiftIds,
        int $pumpOperatorId,
        bool $activeOperatorsOnly
    ): Collection {
        if (! Schema::hasTable('pump_operator_payments')
            || ! Schema::hasTable('pump_operators')
            || ! Schema::hasTable('petro_shifts')) {
            return collect();
        }

        $query = DB::table('pump_operator_payments as pop')
            ->join('pump_operators as po', 'po.id', '=', 'pop.pump_operator_id')
            ->leftJoin('petro_shifts as ps', 'ps.id', '=', 'pop.shift_id')
            ->where('pop.business_id', $businessId)
            ->where('po.business_id', $businessId)
            ->whereIn('pop.shift_id', $shiftIds);

        if ($pumpOperatorId > 0) {
            $query->where('pop.pump_operator_id', $pumpOperatorId);
        }
        if ($activeOperatorsOnly && Schema::hasColumn('pump_operators', 'active')) {
            $query->where('po.active', 1);
        }

        $dateExpression = $this->dateExpression(
            'pump_operator_payments',
            'pop',
            ['transaction_date', 'date', 'date_and_time', 'created_at']
        );
        $statusExpression = Schema::hasColumn('petro_shifts', 'status') ? 'ps.status' : "''";

        return $query
            ->selectRaw('pop.shift_id AS id')
            ->selectRaw('pop.pump_operator_id')
            ->selectRaw('po.name AS operator_name')
            ->selectRaw('pop.shift_id AS display_shift_number')
            ->selectRaw($statusExpression . ' AS status')
            ->selectRaw(($dateExpression ?: 'NULL') . ' AS filter_date')
            ->selectRaw('pop.id AS source_row_id')
            ->selectRaw('40 AS source_priority')
            ->orderByDesc('pop.id')
            ->get();
    }

    private function dateExpression(string $table, string $alias, array $columns): ?string
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $parts = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                $parts[] = 'DATE(' . $alias . '.' . $column . ')';
            }
        }

        return empty($parts) ? null : $this->coalesceExpressions($parts);
    }

    private function coalesceExpressions(array $expressions): ?string
    {
        $expressions = array_values(array_filter($expressions, static function ($expression) {
            return is_string($expression) && trim($expression) !== '';
        }));

        if (empty($expressions)) {
            return null;
        }

        return count($expressions) === 1
            ? $expressions[0]
            : 'COALESCE(' . implode(', ', $expressions) . ')';
    }

    private function applyDateRange($query, ?string $dateExpression, string $startDate, string $endDate): void
    {
        if ($dateExpression !== null && $startDate !== '' && $endDate !== '') {
            $query->whereRaw($dateExpression . ' BETWEEN ? AND ?', [$startDate, $endDate]);
        }
    }
}
