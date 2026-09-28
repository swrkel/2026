<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpaPayment extends Model
{
    use SoftDeletes;

    protected $table = 'hm_spa_payments';
    protected $guarded = ['id'];
}
