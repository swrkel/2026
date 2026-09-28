<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BeautyCustomerVisitNote extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];
}
