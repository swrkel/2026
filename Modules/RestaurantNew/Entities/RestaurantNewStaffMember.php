<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewStaffMember extends RestaurantNewBaseModel
{
    protected $table = 'rn_staff_members';

    protected $casts = [
        'can_take_orders' => 'boolean',
        'can_cashier' => 'boolean',
        'is_active' => 'boolean',
        'service_charge_share_percent' => 'decimal:4',
    ];
}
