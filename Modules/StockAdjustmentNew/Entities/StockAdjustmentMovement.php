<?php

namespace Modules\StockAdjustmentNew\Entities;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentMovement extends Model
{
    protected $table = 'san_stock_adjustment_movements';
    protected $guarded = [];

    protected $casts = [
        'qty_change' => 'decimal:4',
        'cost_change' => 'decimal:4',
        'movement_date' => 'datetime',
    ];
}
