<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantNewBillLine extends RestaurantNewBaseModel
{
    protected $table = 'restaurant_new_bill_lines';

    protected $fillable = [
        'business_id', 'location_id', 'restaurant_new_bill_id', 'restaurant_new_order_line_id',
        'menu_item_id', 'item_name', 'variant_name', 'quantity', 'unit_price', 'discount_amount',
        'tax_amount', 'service_charge_amount', 'line_total', 'line_status', 'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'service_charge_amount' => 'decimal:4',
        'line_total' => 'decimal:4',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(RestaurantNewBill::class, 'restaurant_new_bill_id');
    }
}
