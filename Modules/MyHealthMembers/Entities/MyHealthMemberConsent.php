<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthMemberConsent extends MyHealthBaseModel
{
    protected $table = 'myhealth_member_consents';
    protected $guarded = ['id'];

    protected $dates = ['granted_at', 'expires_at'];
}
