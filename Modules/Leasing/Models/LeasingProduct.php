<?php

namespace Modules\Leasing\Models;

use Illuminate\Database\Eloquent\Model;

class LeasingProduct extends Model
{
    protected $table = 'leasing_products';
    protected $guarded = ['id'];

    public function collateralType()
    {
        return $this->belongsTo(LeaseAssetType::class, 'lease_asset_type_id');
    }
}
