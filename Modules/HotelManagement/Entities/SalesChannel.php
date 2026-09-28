<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesChannel extends Model
{
    use SoftDeletes;

    protected $table = 'hm_sales_channels';
    protected $guarded = [];
}
