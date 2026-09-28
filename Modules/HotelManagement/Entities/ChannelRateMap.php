<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChannelRateMap extends Model
{
    use SoftDeletes;

    protected $table = 'hm_channel_rate_maps';
    protected $guarded = [];
}
