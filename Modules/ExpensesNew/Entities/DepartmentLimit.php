<?php

namespace Modules\ExpensesNew\Entities;

class DepartmentLimit extends BaseModel
{
    protected $table = 'expnew_department_limits';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
