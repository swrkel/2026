<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyServicePackage extends Model
{
    protected $table = 'bs_service_packages';
    protected $guarded = ['id'];
}
