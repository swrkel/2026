<?php

namespace Modules\Membership\Entities;

use Illuminate\Database\Eloquent\Model;
use App\User;

class MembershipType extends Model
{
    protected $fillable = [
        'business_id',
        'type_name',
        'created_by',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

