<?php

namespace Modules\StockAdjustmentNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentLine extends Model
{
    protected $table = 'san_stock_adjustment_lines';
    protected $guarded = [];

    protected $casts = [
        'system_qty' => 'decimal:4',
        'counted_qty' => 'decimal:4',
        'adjustment_qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'cost_amount' => 'decimal:4',
        'expiry_date' => 'date',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }
}
