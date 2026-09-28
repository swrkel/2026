<?php

namespace Modules\SW\Entities;

class DailyCash extends SWModel
{
    protected $table = 'sw_daily_cash';

    protected $casts = ['amount' => 'decimal:4'];

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'sw_shift_id');
    }
}
