<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautySetting extends Model
{
    protected $guarded = ['id'];
    protected $table = 'bs_settings';
}
