<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use SoftDeletes;

    protected $table = 'categories';
    protected $guarded = ['id'];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId)->where(function ($q) {
            $q->whereNull('category_type')->orWhere('category_type', 'product');
        });
    }
}
