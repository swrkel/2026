<?php

namespace Modules\SettlementCore\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * S 639 PHASE 2a: populate petro_shift_lines with SALES, from the existing
 * tables.
 *
 * WHAT THIS IS, AND WHAT IT IS NOT
 * --------------------------------
 * This is a SYNC, not a hook. It derives lines from the source tables and
 * writes any that are missing. It does not touch any existing write path, so it
 * cannot affect what a pump operator or a settlement does today.
 *
 * That choice is deliberate. The obvious alternative - a model observer - does
 * not work here: PetroPD, PumperDashboard, Petro and PetroDirect each declare
 * their own entity over the same tables, so an observer on one silently misses
 * writes made through another. That is the same duplication this whole design
 * exists to remove, and building on top of it would inherit the fault.
 *
 * Deriving from the tables catches every write regardless of which module made
 * it.
 *
 * IDEMPOTENT
 * ----------
 * Every line records source_table + source_id, and the database holds a unique
 * index on (business_id, source_table, source_id, line_type). Running this
 * twice, or a hundred times, produces the same rows.
 *
 * NOTHING READS THIS YET
 * ----------------------
 * Phase 3 compares these totals against the legacy readers. Only when that
 * report is clean does ShiftSaleTotals switch over, in Phase 4.
 *
 * PAYMENTS ARE PHASE 2b
 * ---------------------
 * Sales only here. Payments follow once these figures are proven.
 */
final class ShiftLineSync
{
    /** Rows written per insert batch. */
    private const BATCH = 500;

    /**
     * Build sales lines for one business, optionally one shift.
     *
     * @return array{meter_sale:int, other_sale:int, skipped:int}
     */
    public static function syncSales(int $businessId, ?int $shiftId = null): array
    {
        $result = ['meter_sale' => 0, 'other_sale' => 0, 'skipped' => 0];

        if (! Schema::hasTable('petro_shift_lines')) {
            return $result;
        }

        $result['meter_sale'] = self::syncMeterSales($businessId, $shiftId);
        $result['other_sale'] = self::syncOtherSales($businessId, $shiftId);

        return $result;
    }

    /**
     * Meter sales, from pumper_day_entries - the operator's actual metered work.
     *
     * This is the table the Pumper Dashboard already reads and the one
     * ShiftSaleTotals was pointed at. It is the origin; pump_operator_meter_sales
     * is a copy made at shift close and is deliberately not used here.
     *
     * amount is rounded by the DATABASE to two decimals. pumper_day_entries
     * carries three - litres x price is genuinely fractional - and rounding in
     * SQL over an exact decimal avoids the PHP/JS disagreement that produced the
     * original one-cent fault.
     */
    private static function syncMeterSales(int $businessId, ?int $shiftId): int
    {
        $written = 0;

        $query = DB::table('pumper_day_entries as pde')
            ->leftJoin('pump_operator_assignments as poa', 'pde.pumper_assignment_id', '=', 'poa.id')
            ->where('pde.business_id', $businessId)
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('petro_shift_lines as psl')
                    ->whereColumn('psl.source_id', 'pde.id')
                    ->where('psl.source_table', 'pumper_day_entries')
                    ->where('psl.line_type', 'meter_sale')
                    ->whereColumn('psl.business_id', 'pde.business_id');
            });

        if ($shiftId !== null) {
            $query->where(function ($q) use ($shiftId) {
                $q->where('poa.shift_id', $shiftId);

                if (Schema::hasColumn('pumper_day_entries', 'shift_id')) {
                    $q->orWhere('pde.shift_id', $shiftId);
                }
            });
        }

        $selects = [
            'pde.id as source_id',
            'pde.business_id',
            'pde.pump_operator_id',
            'pde.pumper_assignment_id as assignment_id',
            'pde.pump_id',
            DB::raw('ROUND(COALESCE(pde.amount, 0), 2) as amount'),
            DB::raw('COALESCE(poa.shift_id, NULL) as assignment_shift_id'),
        ];

        foreach (['product_id', 'sold_ltr', 'unit_price', 'location_id', 'created_by'] as $optional) {
            $selects[] = Schema::hasColumn('pumper_day_entries', $optional)
                ? 'pde.' . $optional
                : DB::raw('NULL as ' . $optional);
        }

        $entryShiftColumn = Schema::hasColumn('pumper_day_entries', 'shift_id')
            ? 'pde.shift_id'
            : DB::raw('NULL as shift_id');
        $selects[] = $entryShiftColumn;

        $query->select($selects)->orderBy('pde.id')->chunk(self::BATCH, function ($rows) use (&$written) {
            $insert = [];

            foreach ($rows as $row) {
                $insert[] = [
                    'business_id'      => (int) $row->business_id,
                    'location_id'      => $row->location_id !== null ? (int) $row->location_id : null,
                    'shift_id'         => self::resolveShiftId($row),
                    'pump_operator_id' => $row->pump_operator_id !== null ? (int) $row->pump_operator_id : null,
                    'assignment_id'    => $row->assignment_id !== null ? (int) $row->assignment_id : null,
                    'source_table'     => 'pumper_day_entries',
                    'source_id'        => (int) $row->source_id,
                    'line_type'        => 'meter_sale',
                    'product_id'       => $row->product_id !== null ? (int) $row->product_id : null,
                    'pump_id'          => $row->pump_id !== null ? (int) $row->pump_id : null,
                    'tank_id'          => null,
                    'qty'              => $row->sold_ltr !== null ? (float) $row->sold_ltr : 0,
                    'unit_price'       => $row->unit_price !== null ? (float) $row->unit_price : 0,
                    'amount'           => (float) $row->amount,
                    'created_by'       => $row->created_by !== null ? (int) $row->created_by : null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }

            $written += self::insertIgnore($insert);
        });

        return $written;
    }

    /**
     * Other sales, from pump_operator_other_sales, net of discount.
     *
     * Same expression the Pumper Dashboard uses on the close shift page.
     */
    private static function syncOtherSales(int $businessId, ?int $shiftId): int
    {
        if (! Schema::hasTable('pump_operator_other_sales')) {
            return 0;
        }

        $written = 0;

        $query = DB::table('pump_operator_other_sales as pos')
            ->where('pos.business_id', $businessId)
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('petro_shift_lines as psl')
                    ->whereColumn('psl.source_id', 'pos.id')
                    ->where('psl.source_table', 'pump_operator_other_sales')
                    ->where('psl.line_type', 'other_sale')
                    ->whereColumn('psl.business_id', 'pos.business_id');
            });

        if ($shiftId !== null) {
            $query->where('pos.shift_id', $shiftId);
        }

        $selects = [
            'pos.id as source_id',
            'pos.business_id',
            'pos.shift_id',
            DB::raw('ROUND(COALESCE(pos.sub_total, 0) - COALESCE(pos.discount_amount, 0), 2) as amount'),
        ];

        foreach (['pump_operator_id', 'location_id', 'product_id', 'created_by', 'contact_id'] as $optional) {
            $selects[] = Schema::hasColumn('pump_operator_other_sales', $optional)
                ? 'pos.' . $optional
                : DB::raw('NULL as ' . $optional);
        }

        $query->select($selects)->orderBy('pos.id')->chunk(self::BATCH, function ($rows) use (&$written) {
            $insert = [];

            foreach ($rows as $row) {
                $insert[] = [
                    'business_id'       => (int) $row->business_id,
                    'location_id'       => $row->location_id !== null ? (int) $row->location_id : null,
                    'shift_id'          => $row->shift_id !== null ? (int) $row->shift_id : null,
                    'pump_operator_id'  => $row->pump_operator_id !== null ? (int) $row->pump_operator_id : null,
                    'assignment_id'     => null,
                    'source_table'      => 'pump_operator_other_sales',
                    'source_id'         => (int) $row->source_id,
                    'line_type'         => 'other_sale',
                    'product_id'        => $row->product_id !== null ? (int) $row->product_id : null,
                    'pump_id'           => null,
                    'tank_id'           => null,
                    'qty'               => 0,
                    'unit_price'        => 0,
                    'amount'            => (float) $row->amount,
                    'counterparty_type' => $row->contact_id !== null ? 'contact' : null,
                    'counterparty_id'   => $row->contact_id !== null ? (int) $row->contact_id : null,
                    'created_by'        => $row->created_by !== null ? (int) $row->created_by : null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
            }

            $written += self::insertIgnore($insert);
        });

        return $written;
    }

    /**
     * The assignment's shift wins, falling back to the entry's own column where
     * that exists - the same rule getClosingShiftSummary() applies.
     */
    private static function resolveShiftId($row): ?int
    {
        if (! empty($row->assignment_shift_id)) {
            return (int) $row->assignment_shift_id;
        }

        if (! empty($row->shift_id)) {
            return (int) $row->shift_id;
        }

        return null;
    }

    /**
     * INSERT IGNORE, so a row that already exists is skipped rather than
     * throwing on the unique index.
     *
     * Belt and braces alongside the whereNotExists filter: two syncs running at
     * once would both see a row as missing, and the index is what actually
     * prevents the duplicate.
     */
    private static function insertIgnore(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        try {
            DB::table('petro_shift_lines')->insertOrIgnore($rows);

            return count($rows);
        } catch (\Throwable $e) {
            Log::error('S639 ShiftLineSync insert failed: ' . $e->getMessage());

            return 0;
        }
    }
}
