<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewLinkedBusiness extends Model
{
    use SoftDeletes;

    protected $table = 'mn_linked_businesses';
    protected $guarded = ['id'];
    protected $casts = ['is_active'=>'boolean'];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }


}
