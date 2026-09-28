<?php

namespace Modules\ExpensesNew\Entities;

class CostCenter extends BaseModel
{
    protected $table = 'expnew_cost_centers';
    protected $guarded = ['id'];
}
