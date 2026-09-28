<?php

namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class DistributionRouteUserMap extends Model
{
    protected $table = 'distribution_route_user_maps';

    protected $fillable = [
        'business_id',
        'route_id',
        'sales_rep_id',
        'status',
        'last_status_from',
        'last_status_to',
        'status_changed_at',
        'status_changed_by',
        'added_by',
        'updated_by',
    ];
}
