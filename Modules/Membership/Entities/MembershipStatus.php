<?php

namespace Modules\Membership\Entities;

use Illuminate\Database\Eloquent\Model;
use App\User;

class MembershipStatus extends Model
{
    protected $fillable = [
        'business_id',
        'status_name',
        'created_by',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

