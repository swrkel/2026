<?php
namespace Modules\StockTakingNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockTakeShareDispatch extends Model
{
    protected $table='stk_share_dispatches'; protected $guarded=[]; protected $casts=['payload'=>'array','response'=>'array','sent_at'=>'datetime','failed_at'=>'datetime'];
}
