<?php

namespace Modules\ExpensesNew\Entities;

class ExpensePolicy extends BaseModel
{
    protected $table = 'expnew_policies';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
