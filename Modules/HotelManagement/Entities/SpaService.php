<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpaService extends Model
{
    use SoftDeletes;

    protected $table = 'hm_spa_services';
    protected $guarded = ['id'];
}
