<?php

namespace Modules\DistributionNew\Entities\OperationalPolish;

use Illuminate\Database\Eloquent\Model;

class DeploymentVerification extends Model
{
    protected $table = 'disnew_deployment_verifications';
    protected $guarded = ['id'];
}
