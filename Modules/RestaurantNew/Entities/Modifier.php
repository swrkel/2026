<?php

namespace Modules\RestaurantNew\Entities;

class Modifier extends RestnewModel
{
    protected $table = 'restnew_modifiers';

    protected $casts = [
        'is_active' => 'boolean',
        'price_delta' => 'decimal:4',
    ];

    public function group()
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }
}
