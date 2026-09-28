<?php
namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ScheduleExecutionLog extends Model
{
    protected $table = 'stn_schedule_execution_logs';
    public $updated_at = null;
    protected $guarded = ['id'];
}
