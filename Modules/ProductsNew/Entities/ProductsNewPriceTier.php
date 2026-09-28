<?php
namespace Modules\ProductsNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductsNewPriceTier extends Model
{
    protected $table = 'products_new_price_tiers';
    protected $guarded = ['id'];
    protected $casts = ['price' => 'decimal:4', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
}
