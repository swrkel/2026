<?php

namespace Modules\StockAdjustmentNew\Entities;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentAudit extends Model
{
    protected $table = 'san_stock_adjustment_audits';
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];
}
