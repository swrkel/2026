<?php

namespace Modules\SettlementCore\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S 639 PHASE 3: does the new source agree with the old readers?
 *
 * WHY THIS EXISTS
 * ---------------
 * Phase 4 switches ShiftSaleTotals to read petro_shift_lines. Before that
 * happens, every shift has to be checked against the tables the settlement
 * screens read today. If they disagree, the disagreement has to be explained -
 * not averaged, not rounded away.
 *
 * That matters because the investigation that started this work found real
 * drift: a dedup on row VALUES in the legacy readers could silently drop a
 * genuine second row for the same pump at the same meter reading. Any shift
 * where that happened will show here.
 *
 * READ ONLY
 * ---------
 * This writes nothing, changes nothing, and appears on no screen. It can be run
 * on a live system as often as you like.
 *
 * WHAT IT COMPARES
 * ----------------
 * For every shift in the business:
 *
 *   new_meter_sale     petro_shift_lines, line_type = meter_sale
 *   legacy_day_entries pumper_day_entries  - the origin, what the dashboard reads
 *   legacy_meter_sales pump_operator_meter_sales.balance  - the copy made at close
 *   legacy_details     pump_operator_meter_sale_details.amount  - what the settlement read
 *
 * new_meter_sale and legacy_day_entries should always agree: the sync derives
 * one from the other. A difference there is a fault in the sync itself.
 *
 * Differences against the other two are the ones worth reading. They are the
 * historic drift, and they tell us whether any settled figure was wrong.
 */
final class ShiftLineReconciliation
{
    /** A difference smaller than this is a rounding artifact, not drift. */
    private const TOLERANCE = 0.005;

    /**
     * Compare every shift in a business.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function report(int $businessId, bool $onlyDifferences = true): array
    {
        if (! Schema::hasTable('petro_shift_lines')) {
            return [];
        }

        $rows = [];

        foreach (self::shiftIds($businessId) as $shiftId) {
            $new = self::newMeterSale($businessId, $shiftId);
            $dayEntries = self::legacyDayEntries($businessId, $shiftId);
            $meterSales = self::legacyMeterSales($businessId, $shiftId);
            $details = self::legacyDetails($businessId, $shiftId);

            $newOther = self::newOtherSale($businessId, $shiftId);
            $legacyOther = self::legacyOtherSale($businessId, $shiftId);

            $differences = [];

            if (abs($new - $dayEntries) >= self::TOLERANCE) {
                $differences[] = 'sync_vs_day_entries';
            }

            if ($meterSales !== null && abs($new - $meterSales) >= self::TOLERANCE) {
                $differences[] = 'vs_meter_sales_copy';
            }

            if ($details !== null && abs($new - $details) >= self::TOLERANCE) {
                $differences[] = 'vs_meter_sale_details';
            }

            if (abs($newOther - $legacyOther) >= self::TOLERANCE) {
                $differences[] = 'other_sale';
            }

            if ($onlyDifferences && empty($differences)) {
                continue;
            }

            $rows[] = [
                'shift_id'              => $shiftId,
                'new_meter_sale'        => round($new, 2),
                'legacy_day_entries'    => round($dayEntries, 2),
                'legacy_meter_sales'    => $meterSales === null ? null : round($meterSales, 2),
                'legacy_details'        => $details === null ? null : round($details, 2),
                'new_other_sale'        => round($newOther, 2),
                'legacy_other_sale'     => round($legacyOther, 2),
                'diff_vs_meter_sales'   => $meterSales === null ? null : round($new - $meterSales, 4),
                'diff_vs_details'       => $details === null ? null : round($new - $details, 4),
                'differences'           => implode(', ', $differences),
            ];
        }

        return $rows;
    }

    /**
     * One line summary - what you want before deciding whether to read the detail.
     *
     * @return array<string, mixed>
     */
    public static function summary(int $businessId): array
    {
        $all = self::report($businessId, false);
        $withDifferences = array_values(array_filter($all, fn ($r) => $r['differences'] !== ''));

        return [
            'business_id'      => $businessId,
            'shifts_checked'   => count($all),
            'shifts_clean'     => count($all) - count($withDifferences),
            'shifts_differing' => count($withDifferences),
            'verdict'          => empty($withDifferences)
                ? 'CLEAN - safe to proceed to Phase 4'
                : 'DIFFERENCES FOUND - read the detail before Phase 4',
        ];
    }

    /** @return array<int, int> */
    private static function shiftIds(int $businessId): array
    {
        $ids = DB::table('petro_shift_lines')
            ->where('business_id', $businessId)
            ->whereNotNull('shift_id')
            ->distinct()
            ->pluck('shift_id')
            ->all();

        $assignmentShifts = DB::table('pump_operator_assignments')
            ->where('business_id', $businessId)
            ->whereNotNull('shift_id')
            ->distinct()
            ->pluck('shift_id')
            ->all();

        $ids = array_unique(array_map('intval', array_merge($ids, $assignmentShifts)));
        sort($ids);

        return $ids;
    }

    private static function newMeterSale(int $businessId, int $shiftId): float
    {
        return (float) DB::table('petro_shift_lines')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->where('line_type', 'meter_sale')
            ->sum('amount');
    }

    private static function newOtherSale(int $businessId, int $shiftId): float
    {
        return (float) DB::table('petro_shift_lines')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->where('line_type', 'other_sale')
            ->sum('amount');
    }

    /**
     * The origin - what the Pumper Dashboard reads.
     */
    private static function legacyDayEntries(int $businessId, int $shiftId): float
    {
        $query = DB::table('pumper_day_entries as pde')
            ->leftJoin('pump_operator_assignments as poa', 'pde.pumper_assignment_id', '=', 'poa.id')
            ->where('pde.business_id', $businessId)
            ->where(function ($q) use ($shiftId) {
                $q->where('poa.shift_id', $shiftId);

                if (Schema::hasColumn('pumper_day_entries', 'shift_id')) {
                    $q->orWhere('pde.shift_id', $shiftId);
                }
            });

        $ids = $query->distinct()->pluck('pde.id')->all();

        if (empty($ids)) {
            return 0.0;
        }

        return (float) DB::table('pumper_day_entries')
            ->whereIn('id', $ids)
            ->selectRaw('ROUND(COALESCE(SUM(amount), 0), 2) as total')
            ->value('total');
    }

    /**
     * The copy written at shift close.
     */
    private static function legacyMeterSales(int $businessId, int $shiftId): ?float
    {
        if (! Schema::hasTable('pump_operator_meter_sales')) {
            return null;
        }

        return (float) DB::table('pump_operator_meter_sales')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->selectRaw('ROUND(COALESCE(SUM(balance), 0), 2) as total')
            ->value('total');
    }

    /**
     * What the settlement screens actually read before Phase 4.
     */
    private static function legacyDetails(int $businessId, int $shiftId): ?float
    {
        if (! Schema::hasTable('pump_operator_meter_sale_details')) {
            return null;
        }

        return (float) DB::table('pump_operator_meter_sale_details as d')
            ->join('pump_operator_meter_sales as s', 's.id', '=', 'd.sale_id')
            ->where('s.business_id', $businessId)
            ->where('s.shift_id', $shiftId)
            ->selectRaw('ROUND(COALESCE(SUM(d.amount), 0), 2) as total')
            ->value('total');
    }

    private static function legacyOtherSale(int $businessId, int $shiftId): float
    {
        if (! Schema::hasTable('pump_operator_other_sales')) {
            return 0.0;
        }

        return (float) DB::table('pump_operator_other_sales')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->selectRaw('ROUND(COALESCE(SUM(sub_total) - SUM(discount_amount), 0), 2) as total')
            ->value('total');
    }
}
