<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChannelAvailability extends Model
{
    use SoftDeletes;

    protected $table = 'hm_channel_availability';
    protected $guarded = [];
}
