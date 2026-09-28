<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LinenMovement extends Model
{
    use SoftDeletes;

    protected $table = 'hm_linen_movements';
    protected $guarded = ['id'];
}
