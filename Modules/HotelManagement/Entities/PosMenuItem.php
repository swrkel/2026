<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosMenuItem extends Model
{
    use SoftDeletes;
    protected $table = 'hm_pos_menu_items';
    protected $guarded = ['id'];
}
