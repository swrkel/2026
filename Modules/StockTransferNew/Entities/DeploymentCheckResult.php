<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class DeploymentCheckResult extends Model
{
    protected $table = 'stn_deployment_check_results';

    protected $fillable = [
        'business_id',
        'check_name',
        'status',
        'message',
        'checked_by',
        'checked_at',
    ];

    protected $dates = ['checked_at'];
}
