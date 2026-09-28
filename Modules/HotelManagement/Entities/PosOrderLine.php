<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosOrderLine extends Model
{
    use SoftDeletes;
    protected $table = 'hm_pos_order_lines';
    protected $guarded = ['id'];
}
