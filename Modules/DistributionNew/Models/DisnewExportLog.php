<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewExportLog extends Model
{
    protected $table = 'disnew_export_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'filter_payload' => 'array',
    ];
}
