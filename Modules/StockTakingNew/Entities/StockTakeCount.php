<?php
namespace Modules\StockTakingNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockTakeCount extends Model
{
    protected $table = 'stk_counts'; protected $guarded = [];
    protected $casts = ['counted_qty'=>'decimal:4','counted_at'=>'datetime','metadata'=>'array'];
}
