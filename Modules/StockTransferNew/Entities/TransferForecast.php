<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class TransferForecast extends Model
{
    protected $table = 'stn_transfer_forecasts';

    protected $fillable = [
        'business_id', 'business_location_id', 'store_id', 'product_id', 'forecast_date',
        'period_type', 'opening_stock', 'average_daily_issue', 'lead_time_days',
        'safety_stock_qty', 'suggested_transfer_qty', 'priority', 'status', 'created_by', 'approved_by'
    ];
}
