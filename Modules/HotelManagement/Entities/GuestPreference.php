<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestPreference extends Model
{
    
    protected $table = 'hm_guest_preferences';
    protected $guarded = ['id'];
}
