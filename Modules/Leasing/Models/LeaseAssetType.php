<?php

namespace Modules\Leasing\Models;

use Illuminate\Database\Eloquent\Model;

class LeaseAssetType extends Model
{
    protected $table = 'leasing_lease_asset_types';
    protected $guarded = ['id'];
}
