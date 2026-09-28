<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TailoringBillOfMaterial extends Model
{
    use SoftDeletes;

    protected $table = 'tailoring_bills_of_material';
    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'items' => 'array',
        'measurement_values' => 'array',
        'status_history' => 'array',
        'settings' => 'array',
    ];
}
