<?php

namespace Modules\StockAdjustmentNew\Entities;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentSetting extends Model
{
    protected $table = 'san_stock_adjustment_settings';

    protected $guarded = [];

    protected $casts = [
        'number_padding' => 'integer',
        'default_page_size' => 'integer',
        'quantity_decimals' => 'integer',
        'amount_decimals' => 'integer',
        'require_reason' => 'boolean',
        'require_location' => 'boolean',
        'require_store' => 'boolean',
        'require_approval' => 'boolean',
        'auto_submit' => 'boolean',
        'auto_post_after_approval' => 'boolean',
        'require_batch_when_available' => 'boolean',
        'hide_zero_stock_products' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'allow_zero_unit_cost' => 'boolean',
        'allow_backdated_adjustments' => 'boolean',
        'max_backdate_days' => 'integer',
        'allow_future_dated_adjustments' => 'boolean',
    ];
}
