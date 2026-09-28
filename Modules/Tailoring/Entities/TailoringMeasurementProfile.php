<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringMeasurementProfile extends Model
{
    protected $table = 'tailoring_measurement_profiles';
    protected $guarded = ['id'];
    protected $casts = ['measurements'=>'array','style_preferences'=>'array','is_default'=>'boolean','is_active'=>'boolean'];
}
