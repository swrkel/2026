<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomStatusLog extends Model
{
    
    protected $table = 'hm_room_status_logs';
    protected $guarded = ['id'];
}
