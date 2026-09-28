<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyAuditLog extends Model
{
    protected $guarded = ['id'];
    protected $table = 'bs_audit_logs';
}
