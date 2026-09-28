<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class OperationsException extends Model
{
    protected $table = 'stn_operations_exceptions';

    protected $fillable = [
        'business_id',
        'transfer_id',
        'exception_type',
        'severity',
        'remarks',
        'status',
        'assigned_to',
        'resolved_at',
        'created_by',
        'updated_by',
    ];
}
