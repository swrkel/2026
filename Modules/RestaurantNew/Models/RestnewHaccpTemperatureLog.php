<?php

namespace Modules\RestaurantNew\Models;

use Illuminate\Database\Eloquent\Model;

class RestnewHaccpTemperatureLog extends Model
{
    protected $table = 'restnew_haccp_temperature_logs';

    protected $fillable = [
        'business_id','business_location_id','asset_name','check_type','temperature',
        'min_temperature','max_temperature','status','checked_at','remarks','created_by'
    ];

    protected $casts = ['checked_at' => 'datetime'];

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
