<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrContract extends Model
{
    protected $table = 'hr_contracts';
    protected $guarded = ['id'];
}
