<?php

namespace Modules\PetroPDNew\Entities;

class PdnewDayEndSettlement extends PdnewBaseModel
{
    protected $table = 'pdnew_day_end_settlements';
    protected $casts = [
        'settlement_amount' => 'decimal:4',
        'payment_amount' => 'decimal:4',
        'variance_amount' => 'decimal:4',
    ];
}
