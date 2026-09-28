<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;

class FinanceApprovalWorkflow extends Model
{
    protected $table = 'finance_approval_workflows';

    protected $guarded = [];

    public function levels()
    {
        return $this->hasMany(
            FinanceApprovalWorkflowLevel::class,
            'workflow_id'
        )->orderBy('level_no', 'asc');
    }

    public function location()
    {
        return $this->belongsTo(
            \App\BusinessLocation::class,
            'location_id'
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'created_by'
        );
    }
}