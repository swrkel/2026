<?php

namespace Modules\IdentityAccess\Entities;

use Illuminate\Database\Eloquent\Model;

class IdentityAccessSession extends Model
{
    protected $table = 'identityaccess_sessions';

    protected $fillable = [
        'business_id','location_id','login_identity_id','portal_type','session_token','device_name','browser','ip_address','user_agent','trusted_device','logged_in_at','logged_out_at','expires_at','status'
    ];

    protected $casts = [
        'trusted_device' => 'boolean',
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
