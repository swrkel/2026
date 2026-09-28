<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCostAllocationRule extends Model
{
    use SoftDeletes;

    protected $table = 'expnew_cost_allocation_rules';

    protected $guarded = ['id'];
}
