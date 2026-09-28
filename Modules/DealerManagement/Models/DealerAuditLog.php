<?php
namespace Modules\DealerManagement\Models;

use Illuminate\Database\Eloquent\Model;

class DealerAuditLog extends Model
{
    protected $table = 'dlr_audit_logs';
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
