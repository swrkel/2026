<?php

namespace Modules\PriceChangeNew\Entities;

use Illuminate\Database\Eloquent\Model;

class PriceChangeApplicationLine extends Model
{
    protected $table = 'pcn_price_change_application_lines';
    protected $guarded = ['id'];
    protected $casts = [
        'old_purchase_price_ex_tax' => 'decimal:8',
        'old_purchase_price_inc_tax' => 'decimal:8',
        'old_sell_price_ex_tax' => 'decimal:8',
        'old_sell_price_inc_tax' => 'decimal:8',
        'new_purchase_price_ex_tax' => 'decimal:8',
        'new_purchase_price_inc_tax' => 'decimal:8',
        'new_sell_price_ex_tax' => 'decimal:8',
        'new_sell_price_inc_tax' => 'decimal:8',
        'applied_at' => 'datetime',
        'payload' => 'array',
    ];

    public function application()
    {
        return $this->belongsTo(PriceChangeApplication::class, 'application_id');
    }
}
