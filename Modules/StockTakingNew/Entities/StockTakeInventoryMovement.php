<?php
namespace Modules\StockTakingNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockTakeInventoryMovement extends Model
{
    protected $table='stk_inventory_movements'; protected $guarded=[]; protected $casts=['before_qty'=>'decimal:4','adjustment_qty'=>'decimal:4','after_qty'=>'decimal:4','unit_cost'=>'decimal:4','value_change'=>'decimal:4','posted_at'=>'datetime'];
}
