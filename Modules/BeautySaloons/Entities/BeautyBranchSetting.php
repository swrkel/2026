<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyBranchSetting extends Model
{
    protected $guarded = ['id'];
    protected $table = 'bs_branch_settings';
}
