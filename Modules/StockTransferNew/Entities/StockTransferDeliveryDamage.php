<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class StockTransferDeliveryDamage extends Model
{
    protected $table = 'stn_delivery_damages';

    protected $fillable = [
        'delivery_confirmation_id', 'product_id', 'qty', 'damage_type',
        'estimated_value', 'remarks', 'created_by',
    ];
}
