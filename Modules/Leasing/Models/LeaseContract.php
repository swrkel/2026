<?php

namespace Modules\Leasing\Models;

use Illuminate\Database\Eloquent\Model;

class LeaseContract extends Model
{
    protected $table = 'leasing_lease_contracts';
    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(LeasingProduct::class, 'leasing_product_id');
    }

    public function lease_assets()
    {
        return $this->belongsToMany(LeaseAsset::class, 'leasing_lease_contract_lease_assets', 'leasing_lease_contract_id', 'leasing_lease_asset_id')
            ->withPivot('lease_asset_value')
            ->withTimestamps();
    }

    public function transactions()
    {
        return $this->hasMany(LeasingTransaction::class, 'leasing_lease_contract_id');
    }
}
