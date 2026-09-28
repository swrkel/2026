<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ArchiveRunLine extends Model
{
    protected $table = 'stn_archive_run_lines';

    protected $fillable = [
        'archive_run_id','transfer_id','transfer_no','transfer_date','from_location_id','from_store_id',
        'to_location_id','to_store_id','status','line_count','total_qty','total_value','archive_decision','warning_message'
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'total_qty' => 'decimal:6',
        'total_value' => 'decimal:6',
    ];
}
