<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ReleaseSignOff extends Model
{
    protected $table = 'stn_release_sign_offs';

    protected $fillable = [
        'business_id', 'release_code', 'release_stage', 'signed_by', 'signed_at',
        'status', 'remarks', 'ip_address', 'user_agent'
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];
}
