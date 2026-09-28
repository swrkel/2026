<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TailoringMobileWorkQueue extends Model
{
    use SoftDeletes;

    protected $table = 'tailoring_mobile_work_queues';
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
