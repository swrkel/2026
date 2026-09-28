<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyPayment extends Model
{
    protected $guarded = ['id'];
    protected $table = 'bs_payments';
}
