<?php

namespace Modules\ExpensesNew\Entities;

class BudgetConsumption extends BaseModel
{
    protected $table = 'expnew_budget_consumptions';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
