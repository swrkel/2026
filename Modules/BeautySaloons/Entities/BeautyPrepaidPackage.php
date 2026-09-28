<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyPrepaidPackage extends Model
{
    protected $table = 'bs_prepaid_packages';
    protected $guarded = ['id'];

    public function lines()
    {
        return $this->hasMany(BeautyPrepaidPackageLine::class, 'prepaid_package_id');
    }
}
