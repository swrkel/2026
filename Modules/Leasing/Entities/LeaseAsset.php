<?php

namespace Modules\Leasing\Entities;

use Illuminate\Database\Eloquent\Model;

class LeaseAsset extends Model
{
    protected $table = 'leasing_lease_assets';
    protected $guarded = ['id'];

    public function collateralType()
    {
        return $this->belongsTo(LeaseAssetType::class, 'lease_asset_type_id');
    }

    public function assetLocation()
    {
        return $this->belongsTo(AssetLocation::class, 'asset_location_id');
    }
}
