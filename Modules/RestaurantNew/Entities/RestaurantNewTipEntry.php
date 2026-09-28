<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewTipEntry extends RestaurantNewBaseModel
{
    protected $table = 'rn_tip_entries';

    protected $casts = [
        'amount' => 'decimal:4',
    ];
}
