<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TailoringCustomerExperienceEvent extends Model
{
    use SoftDeletes;

    protected $table = 'tailoring_customer_experience_events';
    protected $guarded = ['id'];

    protected $casts = [
        'settings' => 'array',
        'meta' => 'array',
        'payload' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
