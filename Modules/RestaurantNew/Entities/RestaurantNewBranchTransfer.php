<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewBranchTransfer extends Model
{
    protected $table = 'restaurant_new_branch_transfers';
    protected $guarded = ['id'];
    protected $casts = [
        'transfer_date' => 'date',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'totals' => 'array',
        'meta' => 'array',
    ];
}
