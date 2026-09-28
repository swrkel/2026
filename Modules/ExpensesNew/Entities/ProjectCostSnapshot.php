<?php

namespace Modules\ExpensesNew\Entities;

class ProjectCostSnapshot extends BaseModel
{
    protected $table = 'expnew_project_cost_snapshots';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
