<?php
namespace Modules\StockTakingNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockTakeAssignment extends Model
{
    protected $table='stk_assignments'; protected $guarded=[]; protected $casts=['assigned_at'=>'datetime','completed_at'=>'datetime','scope_json'=>'array'];
}
