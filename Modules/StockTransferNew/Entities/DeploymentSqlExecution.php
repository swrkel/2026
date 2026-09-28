<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class DeploymentSqlExecution extends Model
{
    protected $table = 'stn_deployment_sql_executions';

    protected $fillable = [
        'business_id',
        'script_name',
        'database_scope',
        'executed_by',
        'executed_at',
        'remarks',
    ];

    protected $dates = ['executed_at'];
}
