<?php
namespace Modules\StockTakingNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockTakeSchedule extends Model
{
    protected $table='stk_schedules'; protected $guarded=[]; protected $casts=['next_run_at'=>'datetime','last_run_at'=>'datetime','scope_json'=>'array','is_active'=>'boolean'];
}
