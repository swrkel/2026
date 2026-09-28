<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyRoom extends Model
{
    protected $guarded = ['id'];
    protected $table = 'bs_rooms';
}
