<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyMembershipPlan extends Model
{
    protected $table = 'bs_membership_plans';
    protected $guarded = ['id'];
}
