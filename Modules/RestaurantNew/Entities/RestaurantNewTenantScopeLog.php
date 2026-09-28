<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewTenantScopeLog extends Model
{
    protected $table = 'restaurant_new_tenant_scope_logs';
    protected $guarded = ['id'];
    protected $casts = ['payload' => 'array'];
}
