<?php

namespace Modules\DisStockTransfer\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Distribution\Entities\DistributionVehicles;

class DistributionVehicleStock extends Model
{
    protected $table = 'distribution_vehicle_stocks';

    protected $fillable = [
        'business_id',
        'vehicle_id',
        'product_id',
        'variation_id',
        'qty',
    ];

    /**
     * Relations
     */

    public function vehicle()
    {
        return $this->belongsTo(DistributionVehicles::class, 'vehicle_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }
}
