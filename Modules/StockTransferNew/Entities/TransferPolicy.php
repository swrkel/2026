<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class TransferPolicy extends Model
{
    protected $table = 'stn_transfer_policies';

    protected $fillable = [
        'business_id', 'from_location_id', 'to_location_id', 'from_store_id', 'to_store_id',
        'policy_name', 'priority', 'max_transfer_value', 'requires_cost_allocation',
        'requires_cancellation_approval', 'status', 'created_by', 'updated_by'
    ];
}
