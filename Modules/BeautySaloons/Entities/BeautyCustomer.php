<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyCustomer extends Model
{
    protected $guarded = ['id'];
    protected $table = 'bs_customers';
}
