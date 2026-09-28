<?php

namespace Modules\SW\Entities;

class DailyCreditSale extends SWModel
{
    protected $table = 'sw_daily_credit_sales';

    protected $casts = ['amount' => 'decimal:4'];

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'sw_shift_id');
    }
}
