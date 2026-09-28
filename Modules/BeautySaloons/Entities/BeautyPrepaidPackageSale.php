<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyPrepaidPackageSale extends Model
{
    protected $table = 'bs_prepaid_package_sales';
    protected $guarded = ['id'];
}
