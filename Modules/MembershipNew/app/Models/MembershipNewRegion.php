<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewRegion extends Model
{
    use SoftDeletes;

    protected $table = 'mn_regions';
    protected $guarded = ['id'];
    protected $casts = [
        'date' => 'date',
        'business_id' => 'integer',
        'created_by' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
