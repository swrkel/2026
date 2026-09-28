<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautySale extends Model
{
    protected $guarded = ['id'];
    protected $table = 'bs_sales';
}
