<?php
namespace Modules\RestaurantNew\Entities;

class ModifierGroup extends RestnewModel
{
    protected $table = 'restnew_modifier_groups';
    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

public function modifiers() { return $this->hasMany(Modifier::class, 'modifier_group_id'); }
}
