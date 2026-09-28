<?php

namespace Modules\ReportsOther\Models;

use Illuminate\Database\Eloquent\Model;

class ShareLink extends Model
{
    protected $table = 'reo_share_links';

    protected $fillable = [
        'business_id', 'location_id', 'store_id', 'token', 'channel', 'report_key',
        'payload', 'relative_path', 'download_name', 'mime_type', 'expires_at',
        'created_by', 'downloads', 'last_downloaded_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'downloads' => 'integer',
    ];
}
