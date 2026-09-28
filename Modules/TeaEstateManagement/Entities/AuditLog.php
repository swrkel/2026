<?php
namespace Modules\TeaEstateManagement\Entities;
class AuditLog extends BaseTeaModel
{
    protected $table = 'tea_audit_logs';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
