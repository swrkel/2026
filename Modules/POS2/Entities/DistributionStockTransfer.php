<?php

namespace Modules\DisStockTransfer\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Distribution\Entities\DistributionVehicles;

class DistributionStockTransfer extends Model
{
    protected $table = 'distribution_stock_transfers';

    protected $fillable = [
        'business_id',
        'date',
        'reference_no',
        'location_id',
        'store_id',
        'vehicle_id',
        'product_category_id',
        'note',
        'created_by',
    ];

    /**
     * Relations
     */

    public function lines()
    {
        return $this->hasMany(DistributionStockTransferLine::class, 'stock_transfer_id');
    }

    public function vehicle()
    { 
        return $this->belongsTo(DistributionVehicles::class, 'vehicle_id');
    }

    public function store()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'store_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function category()
    {
        return $this->belongsTo(\App\Category::class, 'product_category_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
