<?php
namespace Modules\StockTakingNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockTakeApproval extends Model
{
    protected $table='stk_approvals'; protected $guarded=[]; protected $casts=['acted_at'=>'datetime'];
}
