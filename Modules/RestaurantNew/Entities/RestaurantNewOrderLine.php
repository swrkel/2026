<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestaurantNewOrderLine extends Model
{
    use SoftDeletes;

    protected $table = 'restaurant_new_order_lines';

    protected $fillable = [
        'business_id', 'location_id', 'order_id', 'menu_item_id', 'variant_id', 'kitchen_section_id',
        'item_name', 'variant_name', 'qty', 'unit_price', 'discount_amount', 'tax_amount', 'line_total',
        'kot_status', 'is_printed_to_kitchen', 'line_note', 'cancel_reason', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'qty' => 'decimal:4', 'unit_price' => 'decimal:4', 'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4', 'line_total' => 'decimal:4', 'is_printed_to_kitchen' => 'boolean',
    ];
}
