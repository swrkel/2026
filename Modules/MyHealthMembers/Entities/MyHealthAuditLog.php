<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthAuditLog extends MyHealthBaseModel
{
    protected $table = 'myhealth_audit_logs';
    protected $guarded = ['id'];
}
