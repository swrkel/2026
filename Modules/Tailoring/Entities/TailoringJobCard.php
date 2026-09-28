<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringJobCard extends Model
{
    protected $table = 'tailoring_job_cards';
    protected $guarded = ['id'];
    protected $casts = ['workflow_log' => 'array', 'materials' => 'array', 'measurements_snapshot' => 'array'];
}
