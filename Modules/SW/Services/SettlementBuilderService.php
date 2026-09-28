<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Assembles what a settlement should show for a set of shifts.
 *
 * Everything here is READ from the daily tabs. The settlement then holds its
 * own figures, and the daily entries are never altered - a shift's records are
 * fixed once it closes.
 *
 * So this returns what was RECORDED. What is SETTLED may differ, and the screen
 * shows both, because an unexplained difference is exactly what someone
 * reconciling is looking for.
 */
class SettlementBuilderService
{
    /** Everything recorded against these shifts. */
    public function assemble(int $businessId, array $shiftIds): array
    {
        if (empty($shiftIds)) {
            return $this->emptyBundle();
        }

        $shifts = DB::table('sw_shifts')
            ->whereIn('id', $shiftIds)
            ->where('business_id', $businessId)
            ->get(['id', 'sw_shift_no', 'shift_date', 'location_id', 'status']);

        return [
            'shifts' => $shifts,
            'shift_numbers' => $shifts->pluck('sw_shift_no')->implode(', '),
            'operators' => $this->operators($shiftIds),
            'meter_sales' => $this->meterSales($businessId, $shifts),
            'cash' => $this->fromTable('sw_daily_cash', $shiftIds, 'current_amount'),
            'cards' => $this->fromTable('sw_daily_cards', $shiftIds, 'amount'),
            'cheques' => $this->fromTable('sw_daily_cheques', $shiftIds, 'amount'),
            'credit_sales' => $this->fromTable('sw_daily_credit_sales', $shiftIds, 'amount'),
            'shortage_excess' => $this->shortageExcess($shiftIds),
        ];
    }

    /**
     * Meter sales, from the pumps at these shifts' location.
     *
     * Opening and closing meters are not in SW's own tables - they are recorded
     * on the Pumper Dashboard. The settlement offers the pumps and lets the
     * user enter the readings, which is what the paper form does.
     */
    protected function meterSales(int $businessId, $shifts): array
    {
        $locationIds = $shifts->pluck('location_id')->unique()->filter()->all();

        if (empty($locationIds) || ! Schema::hasTable('pumps')) {
            return [];
        }

        return DB::table('pumps')
            ->leftJoin('products', 'products.id', '=', 'pumps.product_id')
            ->where('pumps.business_id', $businessId)
            ->whereIn('pumps.location_id', $locationIds)
            ->when(Schema::hasColumn('pumps', 'deleted_at'),
                fn ($q) => $q->whereNull('pumps.deleted_at'))
            ->orderBy('pumps.pump_no')
            ->get([
                'pumps.id as pump_id',
                'pumps.pump_no',
                'pumps.product_id',
                'products.name as product_name',
            ])
            ->all();
    }

    /** One payment type's entries across the shifts. */
    protected function fromTable(string $table, array $shiftIds, string $amountColumn): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $amountColumn)) {
            return ['rows' => collect(), 'total' => 0.0];
        }

        $rows = DB::table($table . ' as t')
            ->leftJoin('pump_operators as po', 'po.id', '=', 't.pump_operator_id')
            ->whereIn('t.sw_shift_id', $shiftIds)
            ->orderBy('t.id')
            ->get(['t.*', 'po.name as operator_name']);

        return [
            'rows' => $rows,
            'total' => round((float) $rows->sum(fn ($r) => (float) $r->{$amountColumn}), 2),
        ];
    }

    /**
     * Shortages and excesses, summed per operator.
     *
     * Only those not already settled - a difference recovered on an earlier
     * settlement must not appear again on this one.
     */
    protected function shortageExcess(array $shiftIds): array
    {
        if (! Schema::hasTable('sw_daily_shortage_excess')) {
            return ['rows' => collect(), 'short' => 0.0, 'excess' => 0.0];
        }

        $rows = DB::table('sw_daily_shortage_excess as se')
            ->leftJoin('pump_operators as po', 'po.id', '=', 'se.pump_operator_id')
            ->whereIn('se.sw_shift_id', $shiftIds)
            ->when(Schema::hasColumn('sw_daily_shortage_excess', 'settled'),
                fn ($q) => $q->where('se.settled', 0))
            ->get(['se.*', 'po.name as operator_name']);

        return [
            'rows' => $rows,
            'short' => round((float) $rows->where('type', 'shortage')->sum('amount'), 2),
            'excess' => round((float) $rows->where('type', 'excess')->sum('amount'), 2),
        ];
    }

    protected function operators(array $shiftIds): array
    {
        return DB::table('sw_shift_operators as so')
            ->join('pump_operators as po', 'po.id', '=', 'so.pump_operator_id')
            ->whereIn('so.sw_shift_id', $shiftIds)
            ->distinct()
            ->orderBy('po.name')
            ->pluck('po.name', 'po.id')
            ->all();
    }

    protected function emptyBundle(): array
    {
        $empty = ['rows' => collect(), 'total' => 0.0];

        return [
            'shifts' => collect(),
            'shift_numbers' => '',
            'operators' => [],
            'meter_sales' => [],
            'cash' => $empty,
            'cards' => $empty,
            'cheques' => $empty,
            'credit_sales' => $empty,
            'shortage_excess' => ['rows' => collect(), 'short' => 0.0, 'excess' => 0.0],
        ];
    }
}
