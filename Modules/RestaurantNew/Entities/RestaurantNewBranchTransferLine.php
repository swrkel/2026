<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewBranchTransferLine extends Model
{
    protected $table = 'restaurant_new_branch_transfer_lines';
    protected $guarded = ['id'];
    protected $casts = [
        'requested_qty' => 'decimal:4',
        'approved_qty' => 'decimal:4',
        'dispatched_qty' => 'decimal:4',
        'received_qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:4',
        'meta' => 'array',
    ];
}
