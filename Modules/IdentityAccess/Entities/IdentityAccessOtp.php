<?php

namespace Modules\IdentityAccess\Entities;

use Illuminate\Database\Eloquent\Model;

class IdentityAccessOtp extends Model
{
    protected $table = 'identityaccess_otps';

    protected $fillable = [
        'business_id','login_identity_id','portal_type','otp_hash','delivery_email','delivery_mobile','email_sent','sms_sent','expires_at','verified_at','attempts','status'
    ];

    protected $casts = [
        'email_sent' => 'boolean',
        'sms_sent' => 'boolean',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];
}
