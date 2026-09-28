<?php

namespace Modules\Leasing\Entities;

use Illuminate\Database\Eloquent\Model;

class LeaseAssetType extends Model
{
    protected $table = 'leasing_lease_asset_types';
    protected $guarded = ['id'];
}
