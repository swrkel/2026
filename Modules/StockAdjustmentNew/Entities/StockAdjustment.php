<?php

namespace Modules\StockAdjustmentNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    protected $table = 'san_stock_adjustments';

    protected $guarded = [];

    protected $casts = [
        'adjustment_date' => 'date',
        'posted_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_qty' => 'decimal:4',
        'total_cost_amount' => 'decimal:4',
        'posting_summary' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLine::class, 'adjustment_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(StockAdjustmentAudit::class, 'adjustment_id');
    }
}
