<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthAccessPasscode extends MyHealthBaseModel
{
    protected $table = 'myhealth_access_passcodes';
    protected $guarded = ['id'];

    protected $dates = ['expires_at', 'used_at'];
}
