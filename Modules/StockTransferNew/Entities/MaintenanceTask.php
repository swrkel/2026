<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class MaintenanceTask extends Model
{
    protected $table = 'stn_maintenance_tasks';

    protected $fillable = [
        'business_id', 'title', 'task_type', 'priority', 'status', 'owner_user_id',
        'due_date', 'description', 'created_by', 'closed_by', 'closed_at'
    ];

    protected $casts = [
        'due_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(MaintenanceLog::class, 'maintenance_task_id');
    }
}
