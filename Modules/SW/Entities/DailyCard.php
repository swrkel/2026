<?php

namespace Modules\SW\Entities;

class DailyCard extends SWModel
{
    protected $table = 'sw_daily_cards';

    protected $casts = ['amount' => 'decimal:4'];

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'sw_shift_id');
    }

    /** Accounts belong to Finance, resolved through config. */
    public function account()
    {
        return $this->belongsTo(config('sw.finance_account_model'), 'card_type_account_id');
    }
}
