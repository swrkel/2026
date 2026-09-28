<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringGarment extends Model
{
    protected $table = 'tailoring_garments';
    protected $guarded = ['id'];
    protected $casts = ['measurement_fields' => 'array'];
}
