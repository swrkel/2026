<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyAppointmentResource extends Model
{
    protected $table = 'bs_appointment_resources';
    protected $guarded = ['id'];
}
