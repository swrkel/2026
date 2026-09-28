<?php
namespace Modules\StockTransferNew\Entities;
use Illuminate\Database\Eloquent\Model;

class ExportQueue extends Model
{
    protected $table = 'stn_export_queue';
    protected $guarded = ['id'];
    protected $casts = ['filters' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
}
