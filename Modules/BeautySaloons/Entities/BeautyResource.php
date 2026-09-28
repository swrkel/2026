<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyResource extends Model
{
    protected $guarded = ['id'];
    protected $table = 'bs_resources';
}
