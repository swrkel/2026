<?php

namespace Modules\PetroPDNew\Entities;

class PdnewDayEnd extends PdnewBaseModel
{
    protected $table = 'pdnew_day_ends';
    protected $casts = [
        'day_end_date' => 'date',
        'settlements_total' => 'decimal:4',
        'payments_total' => 'decimal:4',
        'variance_total' => 'decimal:4',
        'prepared_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function settlements()
    {
        return $this->belongsToMany(PdnewSettlement::class, 'pdnew_day_end_settlements', 'day_end_id', 'settlement_id')->withPivot(['settlement_amount','payment_amount','variance_amount'])->withTimestamps();
    }
}
