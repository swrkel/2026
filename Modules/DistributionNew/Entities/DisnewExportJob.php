<?php

namespace Modules\DistributionNew\Entities;

use Illuminate\Database\Eloquent\Model;

class DisnewExportJob extends Model
{
    protected $table = 'disnew_export_jobs';
    protected $guarded = ['id'];
    protected $casts = [
        'payload_json' => 'array',
        'filters_json' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'sent_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
