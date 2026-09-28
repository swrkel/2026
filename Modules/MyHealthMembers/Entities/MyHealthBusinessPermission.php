<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthBusinessPermission extends MyHealthBaseModel
{
    protected $table = 'myhealth_business_permissions';
    protected $guarded = ['id'];

    protected $casts = [
        'portal_branding' => 'array',
    ];
}
