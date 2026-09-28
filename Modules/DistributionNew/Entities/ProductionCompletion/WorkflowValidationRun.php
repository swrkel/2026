<?php

namespace Modules\DistributionNew\Entities\ProductionCompletion;

use Illuminate\Database\Eloquent\Model;

class WorkflowValidationRun extends Model
{
    protected $table = 'disnew_workflow_validation_runs';
    protected $guarded = ['id'];
}
