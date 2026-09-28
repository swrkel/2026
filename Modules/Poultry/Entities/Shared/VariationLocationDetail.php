<?php

namespace Modules\Poultry\Entities\Shared;

/**
 * The ERP's shared variation_location_details table - the authoritative stock
 * position per variation per location.
 *
 * READ ONLY from this module. Quantity is never written here directly: the
 * arithmetic and its side effects live in the core stock util, reached through
 * Services\StockGateway. Writing qty_available by hand is exactly how stock
 * silently drifts out of agreement with the transaction history.
 */
class VariationLocationDetail extends SharedModel
{
    protected $sharedTableKey = 'variation_location_details';
    protected $table = 'variation_location_details';

    public function variation()
    {
        return $this->belongsTo(Variation::class, 'variation_id');
    }

    /** Available quantity, or 0 when the variation has never been stocked here. */
    public static function availableQty($variationId, $locationId)
    {
        $row = static::query()
            ->where('variation_id', $variationId)
            ->where('location_id', $locationId)
            ->first();

        return $row ? (float) $row->qty_available : 0.0;
    }
}
