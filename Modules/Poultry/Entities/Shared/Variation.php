<?php

namespace Modules\Poultry\Entities\Shared;

/** The ERP's shared variations table. Stock is held per variation. */
class Variation extends SharedModel
{
    protected $sharedTableKey = 'variations';
    protected $table = 'variations';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function locationDetails()
    {
        return $this->hasMany(VariationLocationDetail::class, 'variation_id');
    }

    /** Purchase price excluding tax - used as the default feed issue cost. */
    public function getIssueCostAttribute()
    {
        return $this->default_purchase_price ?: 0;
    }
}
