<?php

namespace Modules\Pawning\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'pawning_articles';
    protected $guarded = ['id'];

    public function collateralType()
    {
        return $this->belongsTo(CollateralType::class, 'collateral_type_id');
    }

    public function vaultLocation()
    {
        return $this->belongsTo(VaultLocation::class, 'vault_location_id');
    }
}
