<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewDuplicateCandidate extends Model
{
    use SoftDeletes;

    protected $table = 'mn_duplicate_candidates';
    protected $guarded = ['id'];

    protected $casts = [
        'match_fields' => 'array',
        'confidence_score' => 'decimal:4',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];
}
