<?php

namespace Modules\ExpensesNew\Entities;

class ApprovalWorkflow extends BaseModel
{
    protected $table = 'expnew_approval_workflows';
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'rules_json' => 'array',
    ];

    public function levels()
    {
        return $this->hasMany(ApprovalLevel::class, 'workflow_id')->orderBy('level_no');
    }
}
