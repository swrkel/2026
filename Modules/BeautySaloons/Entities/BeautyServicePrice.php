<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyServicePrice extends Model
{
    protected $table = 'bs_service_prices';
    protected $guarded = ['id'];
}
