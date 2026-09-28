<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyPrepaidPackageUsage extends Model
{
    protected $table = 'bs_prepaid_package_usages';
    protected $guarded = ['id'];
}
