<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What the Meter Sales entry form needs when a pump is chosen.
 *
 * 8043: the starting meter autoloads from that pump's previous closing, and the
 * unit price autoloads for its product.
 */
class MeterSaleLookupService
{
    /** Pumps at a location, with their product. */
    public function pumps(int $businessId, int $locationId, int $operatorId = 0, array $shiftIds = []): array
    {
        if (! Schema::hasTable('pumps')) {
            return [];
        }

        $shiftIds = array_values(array_unique(array_filter(array_map('intval', $shiftIds))));

        // Settlement SW must never offer a pump before the exact shift/operator
        // context is known. This is the SW equivalent of the shift-isolated
        // Meter Sale source used by PetroPD/PetroDirect previews.
        if ($operatorId <= 0 || empty($shiftIds)
            || ! $this->shiftsBelongToOperatorContext($businessId, $locationId, $operatorId, $shiftIds)) {
            return [];
        }

        $hasProduct = Schema::hasColumn('pumps', 'product_id')
            && Schema::hasTable('products');

        $q = DB::table('pumps');

        if ($hasProduct) {
            $q->leftJoin('products', 'products.id', '=', 'pumps.product_id');
        }

        // Pump schemas differ between older Petro installs. Some keep the
        // business directly on pumps, others inherit it from the product.
        if (Schema::hasColumn('pumps', 'business_id')) {
            $q->where('pumps.business_id', $businessId);
        } elseif ($hasProduct && Schema::hasColumn('products', 'business_id')) {
            $q->where('products.business_id', $businessId);
        } else {
            // Never return another business's pumps merely because an old
            // schema has no usable business ownership column.
            return [];
        }

        if ($locationId > 0 && Schema::hasColumn('pumps', 'location_id')) {
            $q->where('pumps.location_id', $locationId);
        }

        /*
         | SW's Pump Operator record has an optional assigned_pump_id. When it
         | is populated, Meter Sales should offer that operator's related pump
         | rather than every pump in the business. Older operator rows may not
         | have an assignment, so they safely fall back to the location/business
         | pump list instead of becoming unusable.
        */
        if ($operatorId > 0 && Schema::hasTable('pump_operators')
            && Schema::hasColumn('pump_operators', 'assigned_pump_id')) {
            $operator = DB::table('pump_operators')
                ->where('id', $operatorId)
                ->when(Schema::hasColumn('pump_operators', 'business_id'),
                    fn ($oq) => $oq->where('business_id', $businessId))
                ->when($locationId > 0 && Schema::hasColumn('pump_operators', 'location_id'),
                    fn ($oq) => $oq->where('location_id', $locationId))
                ->first(['assigned_pump_id']);

            if ($operator && ! empty($operator->assigned_pump_id)) {
                $q->where('pumps.id', (int) $operator->assigned_pump_id);
            }
        }

        if (Schema::hasColumn('pumps', 'deleted_at')) {
            $q->whereNull('pumps.deleted_at');
        }
        if (Schema::hasColumn('pumps', 'is_active')) {
            $q->where('pumps.is_active', 1);
        }

        $pumpLabel = $this->pumpLabelColumn();
        $select = [
            'pumps.id',
            DB::raw($pumpLabel . ' as pump_no'),
        ];

        if (Schema::hasColumn('pumps', 'product_id')) {
            $select[] = 'pumps.product_id';
        } else {
            $select[] = DB::raw('NULL as product_id');
        }

        if ($hasProduct) {
            $select[] = 'products.name as product_name';
            $select[] = 'products.sku as product_code';
        } else {
            $select[] = DB::raw('NULL as product_name');
            $select[] = DB::raw('NULL as product_code');
        }

        return $q->orderByRaw($pumpLabel . ' ASC')
            ->get($select)
            ->all();
    }

    /** Everything the form fills in when a pump is chosen. */
    public function forPump(int $businessId, int $pumpId, ?int $locationId = null, int $operatorId = 0, array $shiftIds = []): array
    {
        if (! Schema::hasTable('pumps')) {
            return ['found' => false];
        }

        $shiftIds = array_values(array_unique(array_filter(array_map('intval', $shiftIds))));
        if ($operatorId <= 0 || empty($shiftIds)
            || ! $this->shiftsBelongToOperatorContext($businessId, (int) ($locationId ?? 0), $operatorId, $shiftIds)) {
            return ['found' => false];
        }

        // If the operator has an assigned pump, a direct/cached request for a
        // different pump must be refused even if that pump belongs to the same
        // location.
        if (Schema::hasTable('pump_operators') && Schema::hasColumn('pump_operators', 'assigned_pump_id')) {
            $assignedPumpId = DB::table('pump_operators')
                ->where('id', $operatorId)
                ->when(Schema::hasColumn('pump_operators', 'business_id'), fn ($q) => $q->where('business_id', $businessId))
                ->value('assigned_pump_id');

            if (! empty($assignedPumpId) && (int) $assignedPumpId !== $pumpId) {
                return ['found' => false];
            }
        }

        $hasProduct = Schema::hasColumn('pumps', 'product_id')
            && Schema::hasTable('products');

        $q = DB::table('pumps');

        if ($hasProduct) {
            $q->leftJoin('products', 'products.id', '=', 'pumps.product_id');
        }

        $q->where('pumps.id', $pumpId);

        if (Schema::hasColumn('pumps', 'business_id')) {
            $q->where('pumps.business_id', $businessId);
        } elseif ($hasProduct && Schema::hasColumn('products', 'business_id')) {
            $q->where('products.business_id', $businessId);
        } else {
            return ['found' => false];
        }

        if (! empty($locationId) && Schema::hasColumn('pumps', 'location_id')) {
            $q->where('pumps.location_id', (int) $locationId);
        }

        if (Schema::hasColumn('pumps', 'deleted_at')) {
            $q->whereNull('pumps.deleted_at');
        }
        if (Schema::hasColumn('pumps', 'is_active')) {
            $q->where('pumps.is_active', 1);
        }

        $select = [
            'pumps.id',
            DB::raw($this->pumpLabelColumn() . ' as pump_no'),
        ];

        if (Schema::hasColumn('pumps', 'product_id')) {
            $select[] = 'pumps.product_id';
        } else {
            $select[] = DB::raw('NULL as product_id');
        }

        if ($hasProduct) {
            $select[] = 'products.name as product_name';
            $select[] = 'products.sku as product_code';
        } else {
            $select[] = DB::raw('NULL as product_name');
            $select[] = DB::raw('NULL as product_code');
        }

        $pump = $q->first($select);

        if (! $pump) {
            return ['found' => false];
        }

        return [
            'found' => true,
            'pump_no' => $pump->pump_no,
            'product_id' => $pump->product_id,
            'product_name' => $pump->product_name,
            'product_code' => $pump->product_code,
            'starting_meter' => $this->lastClosingMeter($businessId, $pumpId),
            'unit_price' => $this->unitPrice($businessId, (int) $pump->product_id),
        ];
    }

    /**
     * Prove that every selected SW Shift belongs to this business/location and
     * is linked to the selected operator. Never infer this from a same-number
     * shift in another module/database table.
     */
    protected function shiftsBelongToOperatorContext(
        int $businessId,
        int $locationId,
        int $operatorId,
        array $shiftIds
    ): bool {
        if ($businessId <= 0 || $locationId <= 0 || $operatorId <= 0 || empty($shiftIds)
            || ! Schema::hasTable('sw_shifts') || ! Schema::hasTable('sw_shift_operators')) {
            return false;
        }

        $shiftIds = array_values(array_unique(array_filter(array_map('intval', $shiftIds))));
        if (empty($shiftIds)) {
            return false;
        }

        $count = DB::table('sw_shifts as s')
            ->join('sw_shift_operators as so', 'so.sw_shift_id', '=', 's.id')
            ->where('s.business_id', $businessId)
            ->where('s.location_id', $locationId)
            ->where('so.pump_operator_id', $operatorId)
            ->whereIn('s.id', $shiftIds)
            ->distinct()
            ->count('s.id');

        return $count === count($shiftIds);
    }

    protected function pumpLabelColumn(): string
    {
        if (Schema::hasColumn('pumps', 'pump_no') && Schema::hasColumn('pumps', 'pump_name')) {
            return "COALESCE(NULLIF(pumps.pump_no, ''), NULLIF(pumps.pump_name, ''), CONCAT('Pump #', pumps.id))";
        }

        if (Schema::hasColumn('pumps', 'pump_no')) {
            return "COALESCE(NULLIF(pumps.pump_no, ''), CONCAT('Pump #', pumps.id))";
        }

        if (Schema::hasColumn('pumps', 'pump_name')) {
            return "COALESCE(NULLIF(pumps.pump_name, ''), CONCAT('Pump #', pumps.id))";
        }

        return "CONCAT('Pump #', pumps.id)";
    }

    /**
     * That pump's previous closing meter.
     *
     * From PetroGeneral's current_meters and SW's own settlements, most recent
     * winning. PetroPD's tables are not read - PetroPD and SW are alternatives.
     *
     * Both sources every time, not only the first: a site may keep recording in
     * PetroGeneral alongside SW, and taking the older reading would restart the
     * meter behind where the pump actually stands - charging the operator for
     * fuel already accounted for.
     */
    public function lastClosingMeter(int $businessId, int $pumpId, $before = null, $fallback = 0)
    {
        if ($pumpId <= 0 || $businessId <= 0) {
            return $fallback;
        }

        $candidates = [];

        if (Schema::hasTable('sw_settlement_lines') && Schema::hasTable('sw_settlements')) {
            $row = DB::table('sw_settlement_lines as l')
                ->join('sw_settlements as s', 's.id', '=', 'l.settlement_id')
                ->where('s.business_id', $businessId)
                ->where('l.pump_id', $pumpId)
                ->whereNotNull('l.closing_meter')
                ->when(! empty($before), fn ($q) => $q->where('l.created_at', '<', $before))
                ->orderByDesc('l.created_at')
                ->orderByDesc('l.id')
                ->first(['l.closing_meter', 'l.created_at']);

            if ($row) { $candidates[] = $row; }
        }

        /*
         | PetroGeneral's current_meters, and SW's own settlements.
         |
         | PetroGeneral records a reading per pump per operator - current_meter
         | is the closing figure, date_and_time when it was taken. That is where
         | a pump's history lives before SW settles it for the first time.
         |
         | PetroPD's tables are deliberately NOT read: PetroPD and SW are
         | alternatives, and a site runs one or the other.
         |
         | Both sources are consulted every time, not just the first. A site may
         | keep recording in PetroGeneral alongside SW, and taking the older of
         | the two would restart the meter behind where it actually stands.
        */
        if (Schema::hasTable('current_meters')) {
            $row = DB::table('current_meters')
                ->where('business_id', $businessId)
                ->where('pump_id', $pumpId)
                ->whereNotNull('current_meter')
                ->when(! empty($before), fn ($q) => $q->where('date_and_time', '<', $before))
                ->orderByDesc('date_and_time')
                ->orderByDesc('id')
                ->first(['current_meter as closing_meter', 'date_and_time as created_at']);

            if ($row) { $candidates[] = $row; }
        }

        if (empty($candidates)) {
            // Never closed - fall back to the pump's own reading.
            $meterColumns = [];
            foreach (['last_meter_reading', 'starting_meter'] as $column) {
                if (Schema::hasColumn('pumps', $column)) {
                    $meterColumns[] = $column;
                }
            }

            if (empty($meterColumns)) {
                return $fallback;
            }

            $pump = DB::table('pumps')->where('id', $pumpId)->first($meterColumns);

            return $pump->last_meter_reading ?? $pump->starting_meter ?? $fallback;
        }

        usort($candidates, fn ($a, $b) => strcmp(
            (string) ($b->created_at ?? ''), (string) ($a->created_at ?? '')
        ));

        return $candidates[0]->closing_meter;
    }

    /**
     * The selling price for a product.
     *
     * Read here and then STORED on the settlement line. A fuel price changes
     * whenever the tanks are refilled, and re-reading it later would restate
     * what was already sold - the fault behind 414.02 on ep127 and ep163.
     */
    public function unitPrice(int $businessId, int $productId)
    {
        if ($productId <= 0) {
            return 0;
        }

        if (! Schema::hasTable('variations') || ! Schema::hasTable('products')
            || ! Schema::hasColumn('variations', 'sell_price_inc_tax')) {
            return 0;
        }

        return (float) (DB::table('variations')
            ->join('products', 'products.id', '=', 'variations.product_id')
            ->where('products.business_id', $businessId)
            ->where('variations.product_id', $productId)
            ->when(Schema::hasColumn('variations', 'deleted_at'),
                fn ($q) => $q->whereNull('variations.deleted_at'))
            ->orderBy('variations.id')
            ->value('variations.sell_price_inc_tax') ?? 0);
    }
}
