<?php
namespace Modules\ProductsNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductsNewInventoryMovement extends Model
{
    protected $table = 'products_new_inventory_movements';
    protected $guarded = ['id'];
    protected $casts = [
        'movement_date' => 'datetime',
        'qty' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];
}
