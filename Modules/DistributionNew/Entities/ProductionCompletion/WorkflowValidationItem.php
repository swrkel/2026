<?php

namespace Modules\DistributionNew\Entities\ProductionCompletion;

use Illuminate\Database\Eloquent\Model;

class WorkflowValidationItem extends Model
{
    protected $table = 'disnew_workflow_validation_items';
    protected $guarded = ['id'];
}
