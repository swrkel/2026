<?php

namespace Modules\Membership\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;

class MembershipSignature extends Model
{
    protected $fillable = [
        'business_id',
        'signature_path',
        'is_active',
        'created_by',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

