<?php

namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class DistributionSalesOrderLine extends Model
{
    protected $fillable = [
        'sales_order_id',
        'product_id',
        'unit_id',
        'qty',
        'unit_price',
        'amount',
        'discount',
        'discount_type',
        'final_amount',
        'is_free',
        'is_free_bottles',
        'is_free_auto',
    ];

    public function product()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\Product::class, 'product_id');
    }
}
