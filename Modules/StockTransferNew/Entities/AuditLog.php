<?php
namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'stn_audit_logs';
    protected $guarded = ['id'];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'meta' => 'array',
    ];
}
