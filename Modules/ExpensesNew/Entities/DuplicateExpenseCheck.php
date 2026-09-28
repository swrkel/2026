<?php

namespace Modules\ExpensesNew\Entities;

class DuplicateExpenseCheck extends BaseModel
{
    protected $table = 'expnew_duplicate_checks';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
