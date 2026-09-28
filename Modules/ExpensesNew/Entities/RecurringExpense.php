<?php

namespace Modules\ExpensesNew\Entities;

class RecurringExpense extends BaseModel
{
    protected $table = 'expnew_recurring_expenses';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
