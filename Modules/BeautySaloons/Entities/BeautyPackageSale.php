<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyPackageSale extends Model
{
    protected $table = 'bs_package_sales';
    protected $guarded = ['id'];
}
