<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChannelBooking extends Model
{
    use SoftDeletes;

    protected $table = 'hm_channel_bookings';
    protected $guarded = [];
}
