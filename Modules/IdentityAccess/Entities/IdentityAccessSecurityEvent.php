<?php

namespace Modules\IdentityAccess\Entities;

use Illuminate\Database\Eloquent\Model;

class IdentityAccessSecurityEvent extends Model
{
    protected $table = 'identityaccess_security_events';

    protected $fillable = [
        'business_id','location_id','login_identity_id','portal_type','event_type','severity','description','ip_address','user_agent','metadata'
    ];

    protected $casts = ['metadata' => 'array'];
}
