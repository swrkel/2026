<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautySchedulerSlot extends Model
{
    protected $table = 'bs_scheduler_slots';
    protected $guarded = ['id'];

    protected $casts = [
        'slot_date' => 'date',
        'metadata' => 'array',
    ];
}
