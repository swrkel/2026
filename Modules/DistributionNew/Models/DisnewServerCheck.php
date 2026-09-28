<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewServerCheck extends Model
{
    protected $table = 'disnew_server_checks';

    protected $fillable = [
        'business_id',
        'location_id',
        'check_code',
        'check_name',
        'status',
        'message',
        'repair_hint',
        'checked_by',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];
}
