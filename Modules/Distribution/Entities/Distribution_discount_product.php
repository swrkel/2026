<?php

namespace Modules\Distribution\Entities;

use Modules\Distribution\Entities\DistributionUnit as Unit;
use Illuminate\Database\Eloquent\Model;

class Distribution_discount_product extends Model
{
    protected $table = "distribution_discount_products";

    protected $fillable = [
        'discount_id', 'product_id', 'unit_id', 'qty', 'discount_type', 'max_discount',
    ];

    public function discount()
    {
        return $this->belongsTo(Distribution_discount::class, 'discount_id');
    }

    public function product()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\Product::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
