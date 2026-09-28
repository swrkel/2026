<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyAppointment extends Model
{
    protected $guarded = ['id'];
    protected $table = 'bs_appointments';
}
