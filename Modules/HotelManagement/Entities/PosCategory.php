<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosCategory extends Model
{
    use SoftDeletes;
    protected $table = 'hm_pos_categories';
    protected $guarded = ['id'];
}
