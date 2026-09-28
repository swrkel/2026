<?php

namespace Modules\ExpensesNew\Entities;

class Budget extends BaseModel
{
    protected $table = 'expnew_budgets';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
