<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    protected $table = 'stn_maintenance_logs';

    protected $fillable = [
        'business_id', 'maintenance_task_id', 'action', 'remarks', 'created_by'
    ];
}
