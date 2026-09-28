<?php

namespace Modules\ExpensesNew\Entities;

class PolicyRule extends BaseModel
{
    protected $table = 'expnew_policy_rules';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
