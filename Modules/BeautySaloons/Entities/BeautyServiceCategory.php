<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyServiceCategory extends Model
{
    protected $table = 'bs_service_categories';
    protected $guarded = ['id'];
}
