<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;

class FinanceApprovalWorkflowLevel extends Model
{
    protected $table = 'finance_approval_workflow_levels';

    protected $guarded = [];

    public function workflow()
    {
        return $this->belongsTo(
            FinanceApprovalWorkflow::class,
            'workflow_id'
        );
    }
}