<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyWaitlist extends Model
{
    protected $table = 'bs_waitlists';
    protected $guarded = ['id'];

    protected $casts = [
        'preferred_date' => 'date',
        'notified_at' => 'datetime',
        'converted_at' => 'datetime',
    ];
}
