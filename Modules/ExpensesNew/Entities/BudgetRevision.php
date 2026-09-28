<?php

namespace Modules\ExpensesNew\Entities;

class BudgetRevision extends BaseModel
{
    protected $table = 'expnew_budget_revisions';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
