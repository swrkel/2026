<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreItem extends Model
{
    use SoftDeletes;
    protected $table = 'hm_store_items';
    protected $guarded = ['id'];
}
