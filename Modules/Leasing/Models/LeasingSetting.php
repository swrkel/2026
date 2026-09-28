<?php

namespace Modules\Leasing\Models;

use Illuminate\Database\Eloquent\Model;

class LeasingSetting extends Model
{
    protected $table = 'leasing_settings';
    protected $guarded = ['id'];
}
