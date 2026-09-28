<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipNewAuditLog extends Model
{
    protected $table = 'mn_audit_logs';
    protected $guarded = ['id'];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'meta' => 'array',
    ];

    public function scopeForBusiness($query, ?int $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }
}
