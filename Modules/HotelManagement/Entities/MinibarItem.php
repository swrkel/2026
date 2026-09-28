<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MinibarItem extends Model
{
    use SoftDeletes;

    protected $table = 'hm_minibar_items';
    protected $guarded = [];
}
