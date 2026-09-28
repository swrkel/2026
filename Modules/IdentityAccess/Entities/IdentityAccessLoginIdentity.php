<?php

namespace Modules\IdentityAccess\Entities;

use Illuminate\Database\Eloquent\Model;

class IdentityAccessLoginIdentity extends Model
{
    protected $table = 'identityaccess_login_identities';

    protected $fillable = [
        'business_id','location_id','portal_type','owner_type','owner_id','login_identifier','passcode_hash','password_hash','email','mobile','status','last_login_at','locked_until','failed_attempts','metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
    ];
}
