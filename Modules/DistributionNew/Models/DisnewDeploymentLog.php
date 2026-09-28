<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewDeploymentLog extends Model
{
    protected $table = 'disnew_deployment_logs';
    protected $guarded = ['id'];
    protected $casts = ['payload' => 'array'];
}
