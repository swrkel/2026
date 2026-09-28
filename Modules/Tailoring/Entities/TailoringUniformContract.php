<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TailoringUniformContract extends Model
{
    use SoftDeletes;

    protected $table = 'tailoring_uniform_contracts';
    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'items' => 'array',
        'measurement_values' => 'array',
        'status_history' => 'array',
        'settings' => 'array',
    ];
}
