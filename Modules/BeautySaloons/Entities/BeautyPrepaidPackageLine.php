<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyPrepaidPackageLine extends Model
{
    protected $table = 'bs_prepaid_package_lines';
    protected $guarded = ['id'];
}
