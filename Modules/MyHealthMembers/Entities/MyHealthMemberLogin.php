<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthMemberLogin extends MyHealthBaseModel
{
    protected $table = 'myhealth_member_logins';
    protected $guarded = ['id'];
}
