<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomCharge extends Model
{
    
    protected $table = 'hm_room_charges';
    protected $guarded = ['id'];
}
