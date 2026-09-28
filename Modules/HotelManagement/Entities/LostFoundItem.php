<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LostFoundItem extends Model
{
    use SoftDeletes;

    protected $table = 'hm_lost_found_items';
    protected $guarded = ['id'];
}
