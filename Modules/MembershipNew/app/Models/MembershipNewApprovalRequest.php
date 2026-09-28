<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewApprovalRequest extends Model
{
    use SoftDeletes;

    protected $table = 'mn_approval_requests';
    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
