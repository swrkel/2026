<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpaAppointment extends Model
{
    use SoftDeletes;

    protected $table = 'hm_spa_appointments';
    protected $guarded = ['id'];
}
