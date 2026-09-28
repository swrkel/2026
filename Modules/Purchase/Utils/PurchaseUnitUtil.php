<?php

namespace Modules\Purchase\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseUnitUtil
{
    /**
     * @return array{unit_id: int, multiplier: float, allow_decimal: bool}
     */
    public function resolve(int $businessId, int $baseUnitId, ?int $selectedUnitId, mixed $rawSubUnitIds): array
    {
        $selectedUnitId = $selectedUnitId ?: $baseUnitId;
        if ($selectedUnitId <= 0 || ! Schema::hasTable('units')) {
            return ['unit_id' => $baseUnitId, 'multiplier' => 1.0, 'allow_decimal' => true];
        }

        $allowed = $this->parseIds($rawSubUnitIds);
        $allowed[] = $baseUnitId;
        $allowed = array_values(array_unique(array_filter(array_map('intval', $allowed))));
        if (! in_array($selectedUnitId, $allowed, true)) {
            throw new \InvalidArgumentException('The selected unit is not configured for this product.');
        }

        $query = DB::table('units')
            ->where('business_id', $businessId)
            ->where('id', $selectedUnitId);
        if (Schema::hasColumn('units', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        $unit = $query->first(['id', 'base_unit_id', 'base_unit_multiplier', 'allow_decimal']);
        if (! $unit) {
            throw new \InvalidArgumentException('The selected unit is not available for this business.');
        }

        if ($selectedUnitId === $baseUnitId) {
            return ['unit_id' => $selectedUnitId, 'multiplier' => 1.0, 'allow_decimal' => (bool) $unit->allow_decimal];
        }
        if ((int) $unit->base_unit_id !== $baseUnitId) {
            throw new \InvalidArgumentException('The selected sub-unit does not belong to the product base unit.');
        }

        return [
            'unit_id' => $selectedUnitId,
            'multiplier' => max(0.000001, (float) ($unit->base_unit_multiplier ?: 1)),
            'allow_decimal' => (bool) $unit->allow_decimal,
        ];
    }

    /** @return array<int, int> */
    public function parseIds(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('intval', $raw)));
        }

        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('intval', $decoded)));
        }

        if (str_starts_with($raw, 'a:')) {
            $unserialized = @unserialize($raw, ['allowed_classes' => false]);
            if (is_array($unserialized)) {
                return array_values(array_filter(array_map('intval', $unserialized)));
            }
        }

        return array_values(array_filter(array_map('intval', preg_split('/[^0-9]+/', $raw) ?: [])));
    }
}
