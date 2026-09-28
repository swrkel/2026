<?php

namespace Modules\Leasing\Entities;

use Illuminate\Database\Eloquent\Model;

class AssetLocation extends Model
{
    protected $table = 'leasing_asset_locations';
    protected $guarded = ['id'];

    public function getAssetLabelAttribute()
    {
        return trim($this->asset_name . ' ' . $this->shelf_no . ' ' . $this->box_no . ' ' . $this->tray_no);
    }
}
