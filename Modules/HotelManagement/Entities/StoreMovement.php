<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreMovement extends Model
{
    
    protected $table = 'hm_store_movements';
    protected $guarded = ['id'];
}
