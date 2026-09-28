<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class TransferCostAllocation extends Model
{
    protected $table = 'stn_transfer_cost_allocations';

    protected $fillable = [
        'business_id', 'transfer_id', 'cost_center_id', 'vehicle_cost', 'fuel_cost',
        'labour_cost', 'loading_cost', 'unloading_cost', 'misc_cost', 'total_cost',
        'landed_cost_applied', 'remarks', 'created_by', 'updated_by'
    ];
}
