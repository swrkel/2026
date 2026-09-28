<?php
namespace Modules\RestaurantNew\Entities;

class Category extends RestnewModel
{
    protected $table = 'restnew_categories';
    protected $casts = [
        'is_active' => 'boolean',
    ];

public function items() { return $this->hasMany(MenuItem::class, 'category_id'); }
}
