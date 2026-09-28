<?php
namespace Modules\StockTakingNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockTakeAuditLog extends Model
{
    protected $table='stk_audit_logs'; protected $guarded=[]; protected $casts=['old_values'=>'array','new_values'=>'array','metadata'=>'array'];
}
