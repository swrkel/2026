<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyRecurringAppointment extends Model
{
    protected $table = 'bs_recurring_appointments';
    protected $guarded = ['id'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'next_run_date' => 'date',
        'week_days' => 'array',
    ];
}
