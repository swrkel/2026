<?php

namespace Modules\ExpensesNew\Entities;

class BudgetLine extends BaseModel
{
    protected $table = 'expnew_budget_lines';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
