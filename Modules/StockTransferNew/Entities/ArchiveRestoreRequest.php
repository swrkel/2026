<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ArchiveRestoreRequest extends Model
{
    protected $table = 'stn_archive_restore_requests';

    protected $fillable = [
        'business_id','archive_run_id','transfer_id','transfer_no','reason','status',
        'requested_by','requested_at','reviewed_by','reviewed_at','review_remarks'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];
}
