<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringMeasurementTemplate extends Model
{
    protected $fillable = ['business_id','name','gender','fields','is_default','is_active'];
    protected $casts = ['fields'=>'array','is_default'=>'boolean','is_active'=>'boolean'];
}
