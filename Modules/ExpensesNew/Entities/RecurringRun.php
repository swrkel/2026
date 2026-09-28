<?php

namespace Modules\ExpensesNew\Entities;

class RecurringRun extends BaseModel
{
    protected $table = 'expnew_recurring_runs';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
