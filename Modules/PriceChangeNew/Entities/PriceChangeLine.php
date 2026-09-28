<?php

namespace Modules\PriceChangeNew\Entities;

use Illuminate\Database\Eloquent\Model;

class PriceChangeLine extends Model
{
    protected $table = 'pcn_price_change_lines';
    protected $guarded = ['id'];
    protected $casts = [
        'tax_rate' => 'decimal:6',
        'stock_quantity' => 'decimal:4',
        'current_purchase_price_ex_tax' => 'decimal:8',
        'current_purchase_price_inc_tax' => 'decimal:8',
        'current_sell_price_ex_tax' => 'decimal:8',
        'current_sell_price_inc_tax' => 'decimal:8',
        'new_purchase_price_ex_tax' => 'decimal:8',
        'new_purchase_price_inc_tax' => 'decimal:8',
        'new_sell_price_ex_tax' => 'decimal:8',
        'new_sell_price_inc_tax' => 'decimal:8',
        'current_profit_percent' => 'decimal:8',
        'new_profit_percent' => 'decimal:8',
        'actual_before_purchase_price_ex_tax' => 'decimal:8',
        'actual_before_purchase_price_inc_tax' => 'decimal:8',
        'actual_before_sell_price_ex_tax' => 'decimal:8',
        'actual_before_sell_price_inc_tax' => 'decimal:8',
        'actual_after_purchase_price_ex_tax' => 'decimal:8',
        'actual_after_purchase_price_inc_tax' => 'decimal:8',
        'actual_after_sell_price_ex_tax' => 'decimal:8',
        'actual_after_sell_price_inc_tax' => 'decimal:8',
        'applied_at' => 'datetime',
    ];

    public function priceChange()
    {
        return $this->belongsTo(PriceChange::class, 'price_change_id');
    }

    public function scopePrices()
    {
        return $this->hasMany(PriceChangeScopePrice::class, 'price_change_line_id');
    }
}
