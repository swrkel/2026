<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewBranchComparisonSnapshot extends Model
{
    protected $table = 'restaurant_new_branch_comparison_snapshots';
    protected $guarded = ['id'];
    protected $casts = [
        'snapshot_date' => 'date',
        'sales_total' => 'decimal:4',
        'food_cost_total' => 'decimal:4',
        'gross_profit_total' => 'decimal:4',
        'wastage_total' => 'decimal:4',
        'kpi_payload' => 'array',
        'meta' => 'array',
    ];
}
