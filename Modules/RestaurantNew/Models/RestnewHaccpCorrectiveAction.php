<?php

namespace Modules\RestaurantNew\Models;

use Illuminate\Database\Eloquent\Model;

class RestnewHaccpCorrectiveAction extends Model
{
    protected $table = 'restnew_haccp_corrective_actions';

    protected $fillable = [
        'business_id','business_location_id','source_type','source_id','title','description',
        'priority','status','assigned_to','due_at','completed_at','verified_by','verified_at','created_by'
    ];

    protected $casts = ['due_at' => 'datetime', 'completed_at' => 'datetime', 'verified_at' => 'datetime'];

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
