<?php

namespace Modules\ExpensesNew\Entities;

class ApprovalLevel extends BaseModel
{
    protected $table = 'expnew_approval_levels';
    protected $guarded = ['id'];

    protected $casts = [
        'min_amount' => 'decimal:4',
        'max_amount' => 'decimal:4',
        'is_required' => 'boolean',
        'approver_user_ids' => 'array',
        'approver_role_ids' => 'array',
    ];

    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }
}
