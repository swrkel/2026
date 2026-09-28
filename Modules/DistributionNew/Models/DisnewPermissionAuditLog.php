<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewPermissionAuditLog extends Model
{
    protected $table = 'disnew_permission_audit_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'expected_status' => 'boolean',
        'actual_status' => 'boolean',
    ];
}
