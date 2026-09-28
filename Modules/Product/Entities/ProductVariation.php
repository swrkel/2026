<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    protected $table = 'variations';
    protected $guarded = ['id'];

    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
}
