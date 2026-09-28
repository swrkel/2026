<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;

class PdnewOperatorWorkspaceService
{
    public function __construct(private PoneSharedMasterDataService $masterData) {}

    private const ROW_LIMIT = 250;

    /**
     * @return array<string, array{label:string,icon:string,report:?string}>
     */
    public function tabs(): array
    {
        return [
            'pump_operators' => ['label' => 'Pump Operators', 'icon' => 'fa fa-users', 'report' => 'operators'],
            'pumper_excess_shortage_payments' => ['label' => 'Pumper Excess / Shortage Payments', 'icon' => 'fa fa-minus', 'report' => 'shortages'],
            'pumper_day_entries' => ['label' => 'Pumper Day Entries', 'icon' => 'fa fa-calculator', 'report' => 'day_entries'],
            'shift_summary' => ['label' => 'Shift Summary', 'icon' => 'fa fa-clock-o', 'report' => 'shifts'],
            'payment_summary' => ['label' => 'Payment Summary', 'icon' => 'fa fa-money', 'report' => 'operator_payments'],
            'meters_with_payments' => ['label' => 'Meters with Payments', 'icon' => 'fa fa-money', 'report' => 'meters'],
            'daily_pump_status' => ['label' => 'Daily Pump Status', 'icon' => 'fa fa-calculator', 'report' => 'shifts'],
            'close_shift' => ['label' => 'Close Shift', 'icon' => 'fa fa-ban', 'report' => 'shifts'],
            'current_meter' => ['label' => 'Current Meter', 'icon' => 'fa fa-thermometer-full', 'report' => 'meters'],
            'unload_stock' => ['label' => 'Unload Stock', 'icon' => 'fa fa-arrow-down', 'report' => 'unloads'],
            'pd_day_end_settlement' => ['label' => 'Day End – Settlements', 'icon' => 'fa fa-calendar-check-o', 'report' => 'day_end'],
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function operatorOptions(int $businessId, ?int $locationId): Collection
    {
        if (! Schema::hasTable('pdnew_operator_mappings')) {
            return collect();
        }

        return DB::table('pdnew_operator_mappings')
            ->where('business_id', $businessId)
            ->when($locationId, function (Builder $query, int $location): void {
                $query->where(function (Builder $scope) use ($location): void {
                    $scope->where('location_id', $location)
                        ->orWhereNull('location_id');
                });
            })
            ->orderBy('display_name')
            ->get(['pone_operator_profile_id as id', 'display_name']);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function load(string $tab, int $businessId, ?int $locationId, array $filters): array
    {
        return match ($tab) {
            'pump_operators' => $this->pumpOperators($businessId, $locationId, $filters),
            'pumper_excess_shortage_payments' => $this->excessShortage($businessId, $locationId, $filters),
            'pumper_day_entries' => $this->dayEntries($businessId, $locationId, $filters),
            'shift_summary' => $this->shiftSummary($businessId, $locationId, $filters),
            'payment_summary' => $this->paymentSummary($businessId, $locationId, $filters),
            'meters_with_payments' => $this->metersWithPayments($businessId, $locationId, $filters),
            'daily_pump_status' => $this->dailyPumpStatus($businessId, $locationId, $filters),
            'close_shift' => $this->closeShift($businessId, $locationId, $filters),
            'current_meter' => $this->currentMeter($businessId, $locationId, $filters),
            'unload_stock' => $this->unloadStock($businessId, $locationId, $filters),
            'pd_day_end_settlement' => $this->dayEndSettlements($businessId, $locationId, $filters),
            default => ['rows' => collect(), 'totals' => []],
        };
    }

    /** @param array<string, mixed> $filters */
    private function pumpOperators(int $businessId, ?int $locationId, array $filters): array
    {
        if (! Schema::hasTable('pdnew_operator_mappings')) {
            return ['rows' => collect(), 'totals' => $this->emptyOperatorTotals()];
        }

        $from = (string) ($filters['date_from'] ?: now()->toDateString());
        $to = (string) ($filters['date_to'] ?: $from);

        $query = DB::table('pdnew_operator_mappings as map')
            ->where('map.business_id', $businessId);

        if ($locationId) {
            $query->where(function (Builder $scope) use ($locationId): void {
                $scope->where('map.location_id', $locationId)
                    ->orWhereNull('map.location_id');
            });
        }

        $this->applyOperatorSearch($query, 'map.display_name', $filters);
        $this->applyOperatorFilter($query, 'map.pone_operator_profile_id', $filters);

        if (! empty($filters['status'])) {
            $query->where('map.status', $filters['status']);
        }

        $select = [
            'map.id', 'map.display_name', 'map.pone_operator_profile_id',
            'map.pone_pd_operator_id', 'map.user_id', 'map.location_id',
            'map.status', 'map.settings', 'map.last_synced_at',
        ];

        if (Schema::hasTable('pone_operator_ledger_entries')) {
            $currentLedger = DB::table('pone_operator_ledger_entries as ledger')
                ->where('ledger.business_id', $businessId)
                ->where('ledger.status', 'active')
                ->select('ledger.operator_profile_id', DB::raw('COALESCE(SUM(ledger.debit - ledger.credit),0) as current_balance'))
                ->groupBy('ledger.operator_profile_id');

            $periodLedger = DB::table('pone_operator_ledger_entries as ledger')
                ->where('ledger.business_id', $businessId)
                ->where('ledger.status', 'active')
                ->whereDate('ledger.entry_at', '>=', $from)
                ->whereDate('ledger.entry_at', '<=', $to)
                ->select('ledger.operator_profile_id', DB::raw('COALESCE(SUM(ledger.debit - ledger.credit),0) as period_balance'))
                ->groupBy('ledger.operator_profile_id');

            $query->leftJoinSub($currentLedger, 'ledger_all', 'ledger_all.operator_profile_id', '=', 'map.pone_operator_profile_id')
                ->leftJoinSub($periodLedger, 'ledger_period', 'ledger_period.operator_profile_id', '=', 'map.pone_operator_profile_id');
            $select[] = DB::raw('COALESCE(ledger_all.current_balance,0) as current_balance');
            $select[] = DB::raw('COALESCE(ledger_period.period_balance,0) as balance_for_period');
        } else {
            $select[] = DB::raw('0 as current_balance');
            $select[] = DB::raw('0 as balance_for_period');
        }

        if (Schema::hasTable('pone_pump_assignments') && Schema::hasTable('pone_shifts')) {
            $sales = DB::table('pone_pump_assignments as assignment')
                ->join('pone_shifts as shift', 'shift.id', '=', 'assignment.shift_id')
                ->where('assignment.business_id', $businessId)
                ->whereDate('shift.opened_at', '>=', $from)
                ->whereDate('shift.opened_at', '<=', $to)
                ->where('assignment.status', '!=', 'cancelled')
                ->select(
                    'assignment.operator_profile_id',
                    DB::raw('COALESCE(SUM(assignment.sold_quantity),0) as sold_fuel_qty'),
                    DB::raw('COALESCE(SUM(assignment.amount),0) as sale_amount_fuel')
                )
                ->groupBy('assignment.operator_profile_id');

            $query->leftJoinSub($sales, 'sales_period', 'sales_period.operator_profile_id', '=', 'map.pone_operator_profile_id');
            $select[] = DB::raw('COALESCE(sales_period.sold_fuel_qty,0) as sold_fuel_qty');
            $select[] = DB::raw('COALESCE(sales_period.sale_amount_fuel,0) as sale_amount_fuel');
        } else {
            $select[] = DB::raw('0 as sold_fuel_qty');
            $select[] = DB::raw('0 as sale_amount_fuel');
        }

        if (Schema::hasTable('pone_shifts')) {
            $variance = DB::table('pone_shifts as shift')
                ->where('shift.business_id', $businessId)
                ->whereDate('shift.opened_at', '>=', $from)
                ->whereDate('shift.opened_at', '<=', $to)
                ->where('shift.status', '!=', 'cancelled')
                ->select(
                    'shift.operator_profile_id',
                    DB::raw('COALESCE(SUM(shift.excess_amount),0) as excess_amount'),
                    DB::raw('COALESCE(SUM(shift.shortage_amount),0) as short_amount')
                )
                ->groupBy('shift.operator_profile_id');

            $query->leftJoinSub($variance, 'variance_period', 'variance_period.operator_profile_id', '=', 'map.pone_operator_profile_id');
            $select[] = DB::raw('COALESCE(variance_period.excess_amount,0) as excess_amount');
            $select[] = DB::raw('COALESCE(variance_period.short_amount,0) as short_amount');
        } else {
            $select[] = DB::raw('0 as excess_amount');
            $select[] = DB::raw('0 as short_amount');
        }

        if (Schema::hasTable('pone_excess_commissions')) {
            $commission = DB::table('pone_excess_commissions as commission')
                ->where('commission.business_id', $businessId)
                ->where('commission.status', 'confirmed')
                ->whereDate('commission.commission_date', '>=', $from)
                ->whereDate('commission.commission_date', '<=', $to)
                ->select('commission.operator_profile_id', DB::raw('COALESCE(SUM(commission.commission_amount),0) as commission_amount'))
                ->groupBy('commission.operator_profile_id');

            $query->leftJoinSub($commission, 'commission_period', 'commission_period.operator_profile_id', '=', 'map.pone_operator_profile_id');
            $select[] = DB::raw('COALESCE(commission_period.commission_amount,0) as commission_amount');
        } else {
            $select[] = DB::raw('0 as commission_amount');
        }

        $rows = $query->orderBy('map.display_name')
            ->limit(self::ROW_LIMIT)
            ->get($select);

        $rows = $this->enrichOperatorRows($rows);

        return [
            'rows' => $rows,
            'totals' => [
                'active' => $rows->where('status', 'active')->count(),
                'inactive' => $rows->where('status', 'inactive')->count(),
                'current_balance' => (float) $rows->sum('current_balance'),
                'balance_for_period' => (float) $rows->sum('balance_for_period'),
                'sold_fuel_qty' => (float) $rows->sum('sold_fuel_qty'),
                'sale_amount_fuel' => (float) $rows->sum('sale_amount_fuel'),
                'commission_amount' => (float) $rows->sum('commission_amount'),
                'excess_amount' => (float) $rows->sum('excess_amount'),
                'short_amount' => (float) $rows->sum('short_amount'),
            ],
        ];
    }

    /** @return array<string, int|float> */
    private function emptyOperatorTotals(): array
    {
        return [
            'active' => 0, 'inactive' => 0, 'current_balance' => 0.0,
            'balance_for_period' => 0.0, 'sold_fuel_qty' => 0.0,
            'sale_amount_fuel' => 0.0, 'commission_amount' => 0.0,
            'excess_amount' => 0.0, 'short_amount' => 0.0,
        ];
    }

    /** @param array<string, mixed> $filters */
    private function excessShortage(int $businessId, ?int $locationId, array $filters): array
    {
        $shortages = collect();
        $commissions = collect();

        if (Schema::hasTable('pone_shortage_recoveries')) {
            $query = DB::table('pone_shortage_recoveries as item')
                ->leftJoin('pone_shifts as shift', 'shift.id', '=', 'item.shift_id')
                ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                    $join->on('map.business_id', '=', 'item.business_id')
                        ->on('map.pone_operator_profile_id', '=', 'item.operator_profile_id');
                })
                ->where('item.business_id', $businessId);

            $this->scopeLocation($query, 'item', $locationId);
            $this->applyOperatorFilter($query, 'item.operator_profile_id', $filters);
            $this->applyDateRange($query, 'item.recovery_date', $filters);
            $this->applyStatus($query, 'item.status', $filters);

            $shortages = $query->orderByDesc('item.recovery_date')->orderByDesc('item.id')
                ->limit(self::ROW_LIMIT)
                ->get([
                    'item.id', 'item.recovery_number as reference_number', 'item.recovery_date as transaction_date',
                    'item.amount', 'item.payment_method', 'item.reference_no', 'item.status', 'item.note',
                    'shift.shift_number', DB::raw("COALESCE(map.display_name, CONCAT('Operator #', item.operator_profile_id)) as operator_name"),
                ]);
        }

        if (Schema::hasTable('pone_excess_commissions')) {
            $query = DB::table('pone_excess_commissions as item')
                ->leftJoin('pone_shifts as shift', 'shift.id', '=', 'item.shift_id')
                ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                    $join->on('map.business_id', '=', 'item.business_id')
                        ->on('map.pone_operator_profile_id', '=', 'item.operator_profile_id');
                })
                ->where('item.business_id', $businessId);

            $this->scopeLocation($query, 'item', $locationId);
            $this->applyOperatorFilter($query, 'item.operator_profile_id', $filters);
            $this->applyDateRange($query, 'item.commission_date', $filters);
            $this->applyStatus($query, 'item.status', $filters);

            $commissions = $query->orderByDesc('item.commission_date')->orderByDesc('item.id')
                ->limit(self::ROW_LIMIT)
                ->get([
                    'item.id', 'item.commission_number as reference_number', 'item.commission_date as transaction_date',
                    'item.base_excess_amount', 'item.commission_type', 'item.commission_rate',
                    'item.commission_amount as amount', 'item.status', 'item.note',
                    'shift.shift_number', DB::raw("COALESCE(map.display_name, CONCAT('Operator #', item.operator_profile_id)) as operator_name"),
                ]);
        }

        return [
            'shortages' => $shortages,
            'commissions' => $commissions,
            'totals' => [
                'shortage' => (float) $shortages->sum('amount'),
                'commission' => (float) $commissions->sum('amount'),
            ],
        ];
    }

    /** @param array<string, mixed> $filters */
    private function dayEntries(int $businessId, ?int $locationId, array $filters): array
    {
        if (! Schema::hasTable('pone_day_entries')) {
            return ['rows' => collect(), 'totals' => ['quantity' => 0, 'amount' => 0]];
        }

        $query = DB::table('pone_day_entries as item')
            ->leftJoin('pone_shifts as shift', 'shift.id', '=', 'item.shift_id')
            ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                $join->on('map.business_id', '=', 'item.business_id')
                    ->on('map.pone_operator_profile_id', '=', 'item.operator_profile_id');
            })
            ->where('item.business_id', $businessId);

        $this->scopeLocation($query, 'item', $locationId);
        $this->applyOperatorFilter($query, 'item.operator_profile_id', $filters);
        $this->applyDateRange($query, 'item.entry_at', $filters);
        $this->applyStatus($query, 'item.status', $filters);

        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $scope) use ($term): void {
                $scope->where('item.reference_no', 'like', '%' . $term . '%')
                    ->orWhere('shift.shift_number', 'like', '%' . $term . '%')
                    ->orWhere('item.note', 'like', '%' . $term . '%');
            });
        }

        $rows = $query->orderByDesc('item.entry_at')->orderByDesc('item.id')
            ->limit(self::ROW_LIMIT)
            ->get([
                'item.id', 'item.entry_type', 'item.reference_no', 'item.quantity', 'item.amount',
                'item.starting_meter', 'item.closing_meter', 'item.testing_quantity', 'item.entry_at',
                'item.status', 'item.pump_id', 'item.note', 'shift.shift_number',
                DB::raw("COALESCE(map.display_name, CONCAT('Operator #', item.operator_profile_id)) as operator_name"),
            ]);

        return ['rows' => $rows, 'totals' => ['quantity' => (float) $rows->sum('quantity'), 'amount' => (float) $rows->sum('amount')]];
    }

    /** @param array<string, mixed> $filters */
    private function shiftSummary(int $businessId, ?int $locationId, array $filters): array
    {
        if (! Schema::hasTable('pone_shifts')) {
            return ['rows' => collect(), 'totals' => []];
        }

        $query = $this->shiftBaseQuery($businessId, $locationId, $filters);
        $rows = $query->orderByDesc('shift.opened_at')->orderByDesc('shift.id')
            ->limit(self::ROW_LIMIT)
            ->get([
                'shift.id', 'shift.shift_number', 'shift.status', 'shift.opened_at', 'shift.closed_at',
                'shift.meter_sales_total', 'shift.other_sales_total', 'shift.payments_total',
                'shift.expected_total', 'shift.shortage_amount', 'shift.excess_amount',
                DB::raw("COALESCE(map.display_name, CONCAT('Operator #', shift.operator_profile_id)) as operator_name"),
            ]);

        return [
            'rows' => $rows,
            'totals' => [
                'meter_sales' => (float) $rows->sum('meter_sales_total'),
                'other_sales' => (float) $rows->sum('other_sales_total'),
                'payments' => (float) $rows->sum('payments_total'),
                'expected' => (float) $rows->sum('expected_total'),
                'shortage' => (float) $rows->sum('shortage_amount'),
                'excess' => (float) $rows->sum('excess_amount'),
            ],
        ];
    }

    /** @param array<string, mixed> $filters */
    private function paymentSummary(int $businessId, ?int $locationId, array $filters): array
    {
        if (! Schema::hasTable('pone_payments')) {
            return ['rows' => collect(), 'totals' => []];
        }

        $query = DB::table('pone_payments as item')
            ->leftJoin('pone_shifts as shift', 'shift.id', '=', 'item.shift_id')
            ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                $join->on('map.business_id', '=', 'item.business_id')
                    ->on('map.pone_operator_profile_id', '=', 'item.operator_profile_id');
            })
            ->where('item.business_id', $businessId);

        $this->scopeLocation($query, 'item', $locationId);
        $this->applyOperatorFilter($query, 'item.operator_profile_id', $filters);
        $this->applyDateRange($query, 'item.transaction_at', $filters);
        $this->applyStatus($query, 'item.status', $filters);

        if (! empty($filters['payment_type'])) {
            $query->where('item.payment_type', $filters['payment_type']);
        }
        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $scope) use ($term): void {
                $scope->where('item.payment_number', 'like', '%' . $term . '%')
                    ->orWhere('item.reference_no', 'like', '%' . $term . '%')
                    ->orWhere('shift.shift_number', 'like', '%' . $term . '%');
            });
        }

        $rows = $query->orderByDesc('item.transaction_at')->orderByDesc('item.id')
            ->limit(self::ROW_LIMIT)
            ->get([
                'item.id', 'item.payment_number', 'item.payment_type', 'item.gross_amount',
                'item.discount_amount', 'item.amount', 'item.reference_no', 'item.transaction_at',
                'item.status', 'item.note', 'shift.shift_number',
                DB::raw("COALESCE(map.display_name, CONCAT('Operator #', item.operator_profile_id)) as operator_name"),
            ]);

        $byType = $rows->groupBy('payment_type')->map(fn (Collection $items): float => (float) $items->sum('amount'));

        return ['rows' => $rows, 'totals' => ['amount' => (float) $rows->sum('amount'), 'by_type' => $byType]];
    }

    /** @param array<string, mixed> $filters */
    private function metersWithPayments(int $businessId, ?int $locationId, array $filters): array
    {
        if (! Schema::hasTable('pone_pump_assignments') || ! Schema::hasTable('pone_shifts')) {
            return ['rows' => collect(), 'totals' => []];
        }

        $payments = DB::table('pone_payments')
            ->selectRaw('shift_id, SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as payment_amount', ['confirmed'])
            ->where('business_id', $businessId)
            ->groupBy('shift_id');

        $meterTotals = DB::table('pone_pump_assignments')
            ->selectRaw('shift_id, SUM(amount) as shift_meter_amount')
            ->where('business_id', $businessId)
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('shift_id');

        $query = DB::table('pone_pump_assignments as item')
            ->join('pone_shifts as shift', 'shift.id', '=', 'item.shift_id')
            ->leftJoinSub($payments, 'payment_totals', 'payment_totals.shift_id', '=', 'item.shift_id')
            ->leftJoinSub($meterTotals, 'meter_totals', 'meter_totals.shift_id', '=', 'item.shift_id')
            ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                $join->on('map.business_id', '=', 'item.business_id')
                    ->on('map.pone_operator_profile_id', '=', 'item.operator_profile_id');
            })
            ->where('item.business_id', $businessId);

        $this->scopeLocation($query, 'item', $locationId);
        $this->applyOperatorFilter($query, 'item.operator_profile_id', $filters);
        $this->applyDateRange($query, 'shift.opened_at', $filters);
        $this->applyStatus($query, 'shift.status', $filters);

        $rows = $query->orderByDesc('shift.opened_at')->orderByDesc('item.id')
            ->limit(self::ROW_LIMIT)
            ->get([
                'item.id', 'item.pump_id', 'item.opening_meter', 'item.current_meter', 'item.closing_meter',
                'item.testing_quantity', 'item.sold_quantity', 'item.unit_price', 'item.amount as meter_amount',
                'item.status as pump_status', 'shift.id as shift_id', 'shift.shift_number', 'shift.status as shift_status',
                DB::raw('CASE WHEN COALESCE(meter_totals.shift_meter_amount, 0) > 0 THEN COALESCE(payment_totals.payment_amount, 0) * (item.amount / meter_totals.shift_meter_amount) ELSE 0 END as payment_amount'),
                DB::raw('item.amount - (CASE WHEN COALESCE(meter_totals.shift_meter_amount, 0) > 0 THEN COALESCE(payment_totals.payment_amount, 0) * (item.amount / meter_totals.shift_meter_amount) ELSE 0 END) as variance_amount'),
                DB::raw("COALESCE(map.display_name, CONCAT('Operator #', item.operator_profile_id)) as operator_name"),
            ]);

        $paymentTotal = $rows->sum('payment_amount');
        $meterTotal = $rows->sum('meter_amount');

        return [
            'rows' => $rows,
            'totals' => [
                'meter' => (float) $meterTotal,
                'payments' => (float) $paymentTotal,
                'variance' => (float) ($meterTotal - $paymentTotal),
            ],
        ];
    }

    /** @param array<string, mixed> $filters */
    private function dailyPumpStatus(int $businessId, ?int $locationId, array $filters): array
    {
        if (! Schema::hasTable('pone_pump_assignments') || ! Schema::hasTable('pone_shifts')) {
            return [
                'rows' => collect(),
                'pump_cards' => collect(),
                'totals' => $this->emptyDailyPumpStatusTotals(),
            ];
        }

        $query = DB::table('pone_pump_assignments as item')
            ->join('pone_shifts as shift', 'shift.id', '=', 'item.shift_id')
            ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                $join->on('map.business_id', '=', 'item.business_id')
                    ->on('map.pone_operator_profile_id', '=', 'item.operator_profile_id');
            })
            ->where('item.business_id', $businessId)
            ->where('item.status', '<>', 'cancelled');

        if (Schema::hasTable('pone_shift_settlement_references')) {
            $settlements = DB::table('pone_shift_settlement_references as settlement')
                ->where('settlement.business_id', $businessId)
                ->select(
                    'settlement.shift_id',
                    DB::raw('MAX(settlement.settlement_no) as settlement_no'),
                    DB::raw('MAX(settlement.settlement_date) as settlement_date')
                )
                ->groupBy('settlement.shift_id');
            $query->leftJoinSub($settlements, 'settlement_ref', 'settlement_ref.shift_id', '=', 'shift.id');
        }

        $this->scopeLocation($query, 'item', $locationId);
        $this->applyOperatorFilter($query, 'item.operator_profile_id', $filters);
        $this->applyDateRange($query, 'shift.opened_at', $filters);
        $this->applyStatus($query, 'item.status', $filters);

        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $scope) use ($term): void {
                $scope->where('shift.shift_number', 'like', '%' . $term . '%')
                    ->orWhere('map.display_name', 'like', '%' . $term . '%')
                    ->orWhereRaw('CAST(item.pump_id AS CHAR) LIKE ?', ['%' . $term . '%']);
                if (Schema::hasTable('pone_shift_settlement_references')) {
                    $scope->orWhere('settlement_ref.settlement_no', 'like', '%' . $term . '%');
                }
            });
        }

        $select = [
            'item.id', 'item.shift_id', 'item.business_id', 'item.location_id',
            'item.operator_profile_id', 'item.pd_operator_id', 'item.pump_id', 'item.product_id',
            'item.opening_meter', 'item.current_meter', 'item.closing_meter',
            'item.testing_quantity', 'item.sold_quantity', 'item.unit_price', 'item.amount',
            'item.status', 'item.assigned_at', 'item.accepted_at', 'item.confirmed_at', 'item.closed_at',
            'shift.shift_number', 'shift.status as shift_status', 'shift.opened_at', 'shift.closed_at as shift_closed_at',
            DB::raw("COALESCE(map.display_name, CONCAT('Operator #', item.operator_profile_id)) as operator_name"),
        ];
        $select[] = Schema::hasTable('pone_shift_settlement_references')
            ? DB::raw('settlement_ref.settlement_no as settlement_no')
            : DB::raw('NULL as settlement_no');

        $rows = $query->orderByDesc('shift.opened_at')
            ->orderBy('item.pump_id')
            ->orderByDesc('item.id')
            ->limit(self::ROW_LIMIT)
            ->get($select);

        $pumpMasters = $this->masterData->pumps($businessId, $locationId)->keyBy('id');
        $productMasters = $this->masterData->products($businessId, $locationId, null, 10000)->keyBy('id');
        $locationMasters = $this->masterData->locations($businessId)->keyBy('id');

        $rows = $rows->map(function (object $row) use ($pumpMasters, $productMasters, $locationMasters): object {
            $pump = $pumpMasters->get((int) $row->pump_id);
            $product = $productMasters->get((int) ($row->product_id ?? 0));
            $location = $locationMasters->get((int) ($row->location_id ?? 0));

            $row->pump_no = trim((string) ($pump->pump_no ?? ''))
                ?: (trim((string) ($pump->pump_name ?? '')) ?: 'Pump #' . $row->pump_id);
            $row->product_name = trim((string) ($product->name ?? $pump->product_name ?? '')) ?: '—';
            $row->location_name = trim((string) ($location->name ?? '')) ?: 'All / Not assigned';
            $row->date_and_time = $row->opened_at;
            $row->starting_meter = (float) ($row->opening_meter ?? 0);
            $row->testing_ltr = (float) ($row->testing_quantity ?? 0);
            $row->sold_ltr = (float) ($row->sold_quantity ?? 0);
            $row->sold_amount = (float) ($row->amount ?? 0);
            $row->status_key = $this->dailyPumpStatusKey($row);
            $row->status_label = $this->dailyPumpStatusLabel($row->status_key);
            $row->shift_closed = in_array((string) $row->shift_status, ['closed', 'cancelled'], true);
            $row->received_by_operator = ! empty($row->accepted_at) || ! empty($row->confirmed_at) || $row->status === 'open';
            $row->can_edit_assignment = $row->status === 'assigned'
                && ! $row->received_by_operator
                && ! $row->shift_closed;
            $row->can_cancel_assignment = $row->can_edit_assignment;

            return $row;
        });

        $latestByPump = $rows->groupBy(fn (object $row): int => (int) $row->pump_id)
            ->map(fn (Collection $items): ?object => $items->first());

        $pumpCards = $pumpMasters->map(function (object $pump) use ($latestByPump): object {
            $row = $latestByPump->get((int) $pump->id);
            return (object) [
                'pump_id' => (int) $pump->id,
                'pump_no' => trim((string) ($pump->pump_no ?? ''))
                    ?: (trim((string) ($pump->pump_name ?? '')) ?: 'Pump #' . $pump->id),
                'product_name' => trim((string) ($pump->product_name ?? '')) ?: null,
                'status_key' => $row ? $row->status_key : 'available',
                'status_label' => $row ? $row->status_label : 'Available',
                'operator_name' => $row->operator_name ?? null,
                'shift_number' => $row->shift_number ?? null,
                'assignment_id' => $row->id ?? null,
                'shift_id' => $row->shift_id ?? null,
            ];
        })->values();

        if ($pumpCards->isEmpty()) {
            $pumpCards = $latestByPump->values()->map(function (object $row): object {
                return (object) [
                    'pump_id' => (int) $row->pump_id,
                    'pump_no' => $row->pump_no,
                    'product_name' => $row->product_name,
                    'status_key' => $row->status_key,
                    'status_label' => $row->status_label,
                    'operator_name' => $row->operator_name,
                    'shift_number' => $row->shift_number,
                    'assignment_id' => $row->id,
                    'shift_id' => $row->shift_id,
                ];
            });
        }

        return [
            'rows' => $rows,
            'pump_cards' => $pumpCards,
            'totals' => [
                'available' => $pumpCards->where('status_key', 'available')->count(),
                'assigned' => $pumpCards->where('status_key', 'assigned')->count(),
                'received' => $pumpCards->where('status_key', 'received')->count(),
                'closed' => $pumpCards->where('status_key', 'closed')->count(),
                'sold_ltr' => (float) $rows->sum('sold_ltr'),
                'testing_ltr' => (float) $rows->sum('testing_ltr'),
                'sold_amount' => (float) $rows->sum('sold_amount'),
            ],
        ];
    }

    private function dailyPumpStatusKey(object $row): string
    {
        if ((string) ($row->status ?? '') === 'closed'
            || in_array((string) ($row->shift_status ?? ''), ['closed', 'cancelled'], true)) {
            return 'closed';
        }

        if ((string) ($row->status ?? '') === 'open'
            || ! empty($row->accepted_at)
            || ! empty($row->confirmed_at)) {
            return 'received';
        }

        if ((string) ($row->status ?? '') === 'assigned') {
            return 'assigned';
        }

        return 'available';
    }

    private function dailyPumpStatusLabel(string $status): string
    {
        return match ($status) {
            'assigned' => 'Assigned / Waiting to Receive',
            'received' => 'Received',
            'closed' => 'Closed',
            default => 'Available',
        };
    }

    /** @return array<string, int|float> */
    private function emptyDailyPumpStatusTotals(): array
    {
        return [
            'available' => 0,
            'assigned' => 0,
            'received' => 0,
            'closed' => 0,
            'sold_ltr' => 0.0,
            'testing_ltr' => 0.0,
            'sold_amount' => 0.0,
        ];
    }

    /** @param array<string, mixed> $filters */
    private function closeShift(int $businessId, ?int $locationId, array $filters): array
    {
        if (! Schema::hasTable('pone_shifts')) {
            return ['rows' => collect(), 'totals' => []];
        }

        $query = $this->shiftBaseQuery($businessId, $locationId, $filters);
        $rows = $query->orderByRaw("FIELD(shift.status, 'closing', 'open', 'closed', 'planned', 'cancelled')")
            ->orderByDesc('shift.opened_at')
            ->limit(self::ROW_LIMIT)
            ->get([
                'shift.id', 'shift.shift_number', 'shift.status', 'shift.opened_at', 'shift.closed_at',
                'shift.expected_total', 'shift.payments_total', 'shift.shortage_amount', 'shift.excess_amount',
                'shift.integration_status',
                DB::raw("COALESCE(map.display_name, CONCAT('Operator #', shift.operator_profile_id)) as operator_name"),
            ]);

        return [
            'rows' => $rows,
            'totals' => [
                'open' => $rows->where('status', 'open')->count(),
                'closing' => $rows->where('status', 'closing')->count(),
                'closed' => $rows->where('status', 'closed')->count(),
            ],
        ];
    }

    /** @param array<string, mixed> $filters */
    private function currentMeter(int $businessId, ?int $locationId, array $filters): array
    {
        if (! Schema::hasTable('pone_pump_assignments') || ! Schema::hasTable('pone_shifts')) {
            return ['rows' => collect(), 'totals' => []];
        }

        $query = DB::table('pone_pump_assignments as item')
            ->join('pone_shifts as shift', 'shift.id', '=', 'item.shift_id')
            ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                $join->on('map.business_id', '=', 'item.business_id')
                    ->on('map.pone_operator_profile_id', '=', 'item.operator_profile_id');
            })
            ->where('item.business_id', $businessId);

        $this->scopeLocation($query, 'item', $locationId);
        $this->applyOperatorFilter($query, 'item.operator_profile_id', $filters);
        $this->applyDateRange($query, 'shift.opened_at', $filters);
        $this->applyStatus($query, 'item.status', $filters);

        $rows = $query->orderByDesc('shift.opened_at')->orderBy('item.pump_id')
            ->limit(self::ROW_LIMIT)
            ->get([
                'item.id', 'item.pump_id', 'item.opening_meter', 'item.current_meter', 'item.closing_meter',
                'item.testing_quantity', 'item.sold_quantity', 'item.status', 'item.updated_at',
                'shift.id as shift_id', 'shift.shift_number', 'shift.status as shift_status',
                DB::raw("COALESCE(map.display_name, CONCAT('Operator #', item.operator_profile_id)) as operator_name"),
            ]);

        return ['rows' => $rows, 'totals' => ['sold_quantity' => (float) $rows->sum('sold_quantity')]];
    }

    /** @param array<string, mixed> $filters */
    private function unloadStock(int $businessId, ?int $locationId, array $filters): array
    {
        if (! Schema::hasTable('pone_unload_stocks')) {
            return ['rows' => collect(), 'totals' => []];
        }

        $query = DB::table('pone_unload_stocks as item')
            ->leftJoin('pone_shifts as shift', 'shift.id', '=', 'item.shift_id')
            ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                $join->on('map.business_id', '=', 'item.business_id')
                    ->on('map.pone_operator_profile_id', '=', 'item.operator_profile_id');
            })
            ->where('item.business_id', $businessId);

        $this->scopeLocation($query, 'item', $locationId);
        $this->applyOperatorFilter($query, 'item.operator_profile_id', $filters);
        $this->applyDateRange($query, 'item.unloaded_at', $filters);
        $this->applyStatus($query, 'item.status', $filters);

        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $scope) use ($term): void {
                $scope->where('item.receipt_number', 'like', '%' . $term . '%')
                    ->orWhere('item.bill_number', 'like', '%' . $term . '%')
                    ->orWhere('item.supplier_reference', 'like', '%' . $term . '%')
                    ->orWhere('shift.shift_number', 'like', '%' . $term . '%');
            });
        }

        $rows = $query->orderByDesc('item.unloaded_at')->orderByDesc('item.id')
            ->limit(self::ROW_LIMIT)
            ->get([
                'item.id', 'item.receipt_number', 'item.bill_number', 'item.supplier_reference', 'item.store_id',
                'item.unloaded_at', 'item.total_quantity', 'item.total_amount', 'item.status', 'item.note',
                'shift.shift_number', DB::raw("COALESCE(map.display_name, CONCAT('Operator #', item.operator_profile_id)) as operator_name"),
            ]);

        return ['rows' => $rows, 'totals' => ['quantity' => (float) $rows->sum('total_quantity'), 'amount' => (float) $rows->sum('total_amount')]];
    }

    /** @param array<string, mixed> $filters */
    private function dayEndSettlements(int $businessId, ?int $locationId, array $filters): array
    {
        if (! Schema::hasTable('pdnew_day_ends')) {
            return ['rows' => collect(), 'totals' => []];
        }

        $query = DB::table('pdnew_day_ends as item')->where('item.business_id', $businessId);
        $this->scopeLocation($query, 'item', $locationId);
        $this->applyDateRange($query, 'item.day_end_date', $filters);
        $this->applyStatus($query, 'item.status', $filters);

        if (! empty($filters['search'])) {
            $query->where('item.day_end_number', 'like', '%' . trim((string) $filters['search']) . '%');
        }

        $rows = $query->orderByDesc('item.day_end_date')->orderByDesc('item.id')
            ->limit(self::ROW_LIMIT)
            ->get([
                'item.id', 'item.day_end_number', 'item.day_end_date', 'item.status', 'item.settlement_count',
                'item.settlements_total', 'item.payments_total', 'item.variance_total',
                'item.prepared_at', 'item.finalized_at', 'item.note',
            ]);

        return [
            'rows' => $rows,
            'totals' => [
                'settlements' => (int) $rows->sum('settlement_count'),
                'amount' => (float) $rows->sum('settlements_total'),
                'payments' => (float) $rows->sum('payments_total'),
                'variance' => (float) $rows->sum('variance_total'),
            ],
        ];
    }

    /** @param array<string, mixed> $filters */
    private function shiftBaseQuery(int $businessId, ?int $locationId, array $filters): Builder
    {
        $query = DB::table('pone_shifts as shift')
            ->leftJoin('pdnew_operator_mappings as map', function ($join): void {
                $join->on('map.business_id', '=', 'shift.business_id')
                    ->on('map.pone_operator_profile_id', '=', 'shift.operator_profile_id');
            })
            ->where('shift.business_id', $businessId);

        $this->scopeLocation($query, 'shift', $locationId);
        $this->applyOperatorFilter($query, 'shift.operator_profile_id', $filters);
        $this->applyDateRange($query, 'shift.opened_at', $filters);
        $this->applyStatus($query, 'shift.status', $filters);

        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $scope) use ($term): void {
                $scope->where('shift.shift_number', 'like', '%' . $term . '%')
                    ->orWhere('map.display_name', 'like', '%' . $term . '%');
            });
        }

        return $query;
    }

    /**
     * @param Collection<int, object> $rows
     * @return Collection<int, object>
     */
    private function enrichOperatorRows(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $users = collect();
        $userIds = $rows->pluck('user_id')->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        if ($userIds->isNotEmpty() && Schema::hasTable('users') && Schema::hasColumn('users', 'id')) {
            $columns = ['id'];
            foreach (['surname', 'first_name', 'last_name', 'username', 'email'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columns[] = $column;
                }
            }
            $users = DB::table('users')->whereIn('id', $userIds->all())->get($columns)->keyBy('id');
        }

        $locations = collect();
        $locationIds = $rows->pluck('location_id')->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        if ($locationIds->isNotEmpty()
            && Schema::hasTable('business_locations')
            && Schema::hasColumn('business_locations', 'id')) {
            $columns = ['id'];
            if (Schema::hasColumn('business_locations', 'name')) {
                $columns[] = 'name';
            }
            $locations = DB::table('business_locations')->whereIn('id', $locationIds->all())->get($columns)->keyBy('id');
        }

        return $rows->map(function (object $row) use ($users, $locations): object {
            $user = $users->get((int) ($row->user_id ?? 0));
            $name = '';
            if ($user) {
                $name = trim(implode(' ', array_filter([
                    $user->surname ?? null,
                    $user->first_name ?? null,
                    $user->last_name ?? null,
                ])));
                if ($name === '') {
                    $name = trim((string) ($user->username ?? $user->email ?? ''));
                }
            }

            $location = $locations->get((int) ($row->location_id ?? 0));
            $settings = json_decode((string) ($row->settings ?? ''), true);
            $settings = is_array($settings) ? $settings : [];
            $master = (array) data_get($settings, 'operator_master', []);
            $local = (array) data_get($settings, 'local_overrides', []);

            $row->display_name = trim((string) ($local['display_name'] ?? '')) ?: (string) $row->display_name;
            $row->user_name = $name !== '' ? $name : 'Not linked';
            $row->location_name = $location && ! empty($location->name)
                ? (string) $location->name
                : 'All / Not assigned';
            $row->source_login_enabled = (bool) data_get($settings, 'source_login_enabled', false);
            $row->commission_type = (string) ($local['commission_type'] ?? $master['commission_type'] ?? 'none');
            $row->commission_rate = (float) ($local['commission_rate'] ?? $master['commission_ap'] ?? 0);
            $row->mobile = (string) ($local['mobile'] ?? $master['mobile'] ?? '');
            $row->landline = (string) ($local['landline'] ?? $master['landline'] ?? '');
            $row->cnic = (string) ($local['cnic'] ?? $master['cnic'] ?? '');
            $row->address = (string) ($local['address'] ?? $master['address'] ?? '');
            $row->dob = (string) ($local['dob'] ?? $master['dob'] ?? '');
            $row->is_default = (int) ($master['is_default'] ?? 0);
            $row->current_balance = (float) ($row->current_balance ?? 0);
            $row->balance_for_period = (float) ($row->balance_for_period ?? 0);
            $row->sold_fuel_qty = (float) ($row->sold_fuel_qty ?? 0);
            $row->sale_amount_fuel = (float) ($row->sale_amount_fuel ?? 0);
            $row->commission_amount = (float) ($row->commission_amount ?? 0);
            $row->excess_amount = (float) ($row->excess_amount ?? 0);
            $row->short_amount = (float) ($row->short_amount ?? 0);

            return $row;
        });
    }

    private function scopeLocation(Builder $query, string $alias, ?int $locationId): void
    {
        if ($locationId) {
            $query->where($alias . '.location_id', $locationId);
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyDateRange(Builder $query, string $column, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyOperatorFilter(Builder $query, string $column, array $filters): void
    {
        if (! empty($filters['operator_profile_id'])) {
            $query->where($column, (int) $filters['operator_profile_id']);
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyStatus(Builder $query, string $column, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where($column, $filters['status']);
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyOperatorSearch(Builder $query, string $column, array $filters): void
    {
        if (! empty($filters['search'])) {
            $query->where($column, 'like', '%' . trim((string) $filters['search']) . '%');
        }
    }
}
