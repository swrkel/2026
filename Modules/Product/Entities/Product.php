<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $guarded = ['id'];

    protected $casts = [
        'enable_stock' => 'boolean',
        'not_for_selling' => 'boolean',
        'is_inactive' => 'boolean',
        'alert_quantity' => 'decimal:4',
    ];

    public function category() { return $this->belongsTo(ProductCategory::class, 'category_id'); }
    public function brand() { return $this->belongsTo(ProductBrand::class, 'brand_id'); }
    public function unit() { return $this->belongsTo(ProductUnit::class, 'unit_id'); }
    public function variations() { return $this->hasMany(ProductVariation::class, 'product_id'); }
}
