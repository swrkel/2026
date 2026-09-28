<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyReceptionCheckin extends Model
{
    protected $table = 'bs_reception_checkins';
    protected $guarded = ['id'];

    protected $casts = [
        'checkin_at' => 'datetime',
        'checkout_at' => 'datetime',
    ];
}
