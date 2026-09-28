<?php

namespace Modules\StockAdjustmentNew\Entities;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentAccountMapping extends Model
{
    protected $table = 'san_stock_adjustment_account_mappings';

    protected $guarded = [];

    protected $casts = [
        'effective_from' => 'datetime',
        'is_active' => 'boolean',
    ];
}
