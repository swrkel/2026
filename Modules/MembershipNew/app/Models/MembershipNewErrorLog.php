<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipNewErrorLog extends Model
{
    protected $table = 'mn_error_logs';
    protected $guarded = ['id'];

    protected $casts = [
        'context' => 'array',
    ];

    public function scopeForBusiness($query, ?int $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }
}
