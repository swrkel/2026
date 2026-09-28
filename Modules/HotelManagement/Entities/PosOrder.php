<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosOrder extends Model
{
    use SoftDeletes;
    protected $table = 'hm_pos_orders';
    protected $guarded = ['id'];
}
