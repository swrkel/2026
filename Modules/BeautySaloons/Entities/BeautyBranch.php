<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyBranch extends Model
{
    protected $guarded = ['id'];
    protected $table = 'bs_branches';
}
