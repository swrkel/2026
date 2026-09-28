<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewHealthCheck extends Model
{
    protected $table = 'disnew_health_checks';

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'checked_at' => 'datetime',
    ];
}
