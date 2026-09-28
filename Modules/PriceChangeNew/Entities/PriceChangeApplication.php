<?php

namespace Modules\PriceChangeNew\Entities;

use Illuminate\Database\Eloquent\Model;

class PriceChangeApplication extends Model
{
    protected $table = 'pcn_price_change_applications';
    protected $guarded = ['id'];
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function priceChange()
    {
        return $this->belongsTo(PriceChange::class, 'price_change_id');
    }

    public function lines()
    {
        return $this->hasMany(PriceChangeApplicationLine::class, 'application_id');
    }
}
