<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyChair extends Model
{
    protected $guarded = ['id'];
    protected $table = 'bs_chairs';
}
