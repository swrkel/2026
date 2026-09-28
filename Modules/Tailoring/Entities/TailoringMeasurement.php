<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringMeasurement extends Model
{
    protected $table = 'tailoring_measurements';
    protected $guarded = ['id'];
    protected $casts = ['measurements' => 'array'];
}
