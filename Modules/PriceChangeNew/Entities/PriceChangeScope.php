<?php

namespace Modules\PriceChangeNew\Entities;

use Illuminate\Database\Eloquent\Model;

class PriceChangeScope extends Model
{
    protected $table = 'pcn_price_change_scopes';
    protected $guarded = ['id'];

    public function priceChange()
    {
        return $this->belongsTo(PriceChange::class, 'price_change_id');
    }
}
