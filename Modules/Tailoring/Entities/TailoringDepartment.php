<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringDepartment extends Model
{
    protected $table = 'tailoring_departments';
    protected $guarded = ['id'];
    protected $casts = ['is_active'=>'boolean'];
}
