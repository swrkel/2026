<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthMobileAccessToken extends MyHealthBaseModel
{
    protected $table = 'myhealth_mobile_access_tokens';
    protected $guarded = ['id'];
    protected $dates = ['expires_at', 'revoked_at', 'last_used_at'];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }
}
