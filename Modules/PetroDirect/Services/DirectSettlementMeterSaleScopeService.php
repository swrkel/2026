<?php

namespace Modules\PetroDirect\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Modules\PetroDirect\Entities\MeterSale;
use Modules\PetroDirect\Entities\Settlement;

/**
 * One authoritative Meter Sale source for Petro Direct settlement screens.
 *
 * Petro Direct shares the legacy meter_sales table with older Petro/PetroPD
 * workflows.  A Direct Settlement preview/finalize/print must therefore start
 * with settlement ownership and then apply the Direct shift rules; it must
 * never broaden ownership through PumpOperatorAssignment/PetroPD shifts.
 */
class DirectSettlementMeterSaleScopeService
{
    /** @return array<int> */
    public function numericShiftIds(Settlement $settlement): array
    {
        $raw = $settlement->work_shift;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }

        if (! is_array($raw)) {
            $raw = [$raw];
        }

        $ids = [];
        foreach ($raw as $value) {
            if (is_int($value) || (is_string($value) && preg_match('/^\s*\d+\s*$/', $value))) {
                $id = (int) $value;
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public function isCanonicalDirectPair(Settlement $settlement): bool
    {
        $number = strtoupper(trim((string) $settlement->settlement_no));
        if (! preg_match('/^DST\d+$/', $number)) {
            return false;
        }

        $raw = $settlement->work_shift;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }
        if (! is_array($raw)) {
            $raw = [$raw];
        }

        foreach ($raw as $value) {
            if (strtoupper(trim((string) $value)) === $number) {
                return true;
            }
        }

        return false;
    }

    public function rows(Settlement $settlement, int $businessId): Collection
    {
        if ($businessId <= 0 || (int) $settlement->id <= 0) {
            return collect();
        }

        $query = MeterSale::query()
            ->where('business_id', $businessId)
            ->where('settlement_no', (int) $settlement->id)
            ->petroDirectOwned();

        if (Schema::hasColumn('meter_sales', 'shift_id')) {
            $shiftIds = $this->numericShiftIds($settlement);

            if ($this->isCanonicalDirectPair($settlement)) {
                // New Petro Direct records deliberately do not carry an
                // operational/PetroPD shift id.  Positive shift ids here are a
                // foreign/shared-table row and must never enter the Direct view.
                $query->where(function ($shiftQuery) {
                    $shiftQuery->whereNull('shift_id')->orWhere('shift_id', 0);
                });
            } elseif (! empty($shiftIds)) {
                // Historical Direct records can carry a real legacy shift id.
                // Keep exact-shift rows plus valid manual Direct rows which did
                // not store shift_id.
                $query->where(function ($shiftQuery) use ($shiftIds) {
                    $shiftQuery->whereIn('shift_id', $shiftIds)
                        ->orWhereNull('shift_id')
                        ->orWhere('shift_id', 0);
                });
            }
            // Old records whose work_shift is a synthetic label have no safe
            // numeric shift authority.  For those, exact settlement ownership
            // above is the compatibility-safe authority; never guess via a PD
            // assignment table.
        }

        return $query->with(['pump', 'product'])->orderBy('id')->get();
    }

    public function apply(Settlement $settlement, int $businessId): Settlement
    {
        $settlement->setRelation('meter_sales', $this->rows($settlement, $businessId));

        return $settlement;
    }
}
