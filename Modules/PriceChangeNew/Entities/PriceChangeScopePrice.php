<?php

namespace Modules\PriceChangeNew\Entities;

use Illuminate\Database\Eloquent\Model;

class PriceChangeScopePrice extends Model
{
    protected $table = 'pcn_price_change_scope_prices';
    protected $guarded = ['id'];
    protected $casts = [
        'current_group_price_inc_tax' => 'decimal:8',
        'new_group_price_inc_tax' => 'decimal:8',
        'applied_from_price_inc_tax' => 'decimal:8',
        'applied_to_price_inc_tax' => 'decimal:8',
        'applied_at' => 'datetime',
    ];

    public function priceChange()
    {
        return $this->belongsTo(PriceChange::class, 'price_change_id');
    }

    public function line()
    {
        return $this->belongsTo(PriceChangeLine::class, 'price_change_line_id');
    }
}
