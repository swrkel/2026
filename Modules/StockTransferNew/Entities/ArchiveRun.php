<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ArchiveRun extends Model
{
    protected $table = 'stn_archive_runs';

    protected $fillable = [
        'business_id','location_id','store_id','archive_year','archive_until','status',
        'total_completed_transfers','total_lines','total_qty','total_value','export_file','checksum',
        'preview_by','preview_at','archived_by','archived_at','cancelled_by','cancelled_at',
        'remarks','created_by','updated_by'
    ];

    protected $casts = [
        'archive_until' => 'date',
        'preview_at' => 'datetime',
        'archived_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_qty' => 'decimal:6',
        'total_value' => 'decimal:6',
    ];

    public function lines()
    {
        return $this->hasMany(ArchiveRunLine::class, 'archive_run_id');
    }
}
