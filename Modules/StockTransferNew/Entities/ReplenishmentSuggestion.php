<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ReplenishmentSuggestion extends Model
{
    protected $table = 'stn_replenishment_suggestions';

    protected $fillable = [
        'business_id', 'business_location_id', 'store_id', 'from_location_id', 'from_store_id',
        'product_id', 'current_stock', 'minimum_stock', 'maximum_stock', 'suggested_qty',
        'reason', 'priority', 'status', 'converted_transfer_id', 'created_by'
    ];
}
