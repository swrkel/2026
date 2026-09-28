<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewMergeRequest extends Model
{
    use SoftDeletes;

    protected $table = 'mn_merge_requests';
    protected $guarded = ['id'];

    protected $casts = [
        'merge_payload' => 'array',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function primaryMember()
    {
        return $this->belongsTo(MembershipNewCentralMember::class, 'primary_central_member_id');
    }

    public function duplicateMember()
    {
        return $this->belongsTo(MembershipNewCentralMember::class, 'duplicate_central_member_id');
    }
}
