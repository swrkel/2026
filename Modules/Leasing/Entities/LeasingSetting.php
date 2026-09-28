<?php

namespace Modules\Leasing\Entities;

use Illuminate\Database\Eloquent\Model;

class LeasingSetting extends Model
{
    protected $table = 'leasing_settings';
    protected $guarded = ['id'];
}
