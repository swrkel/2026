<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseActivityCost extends Model
{
    use SoftDeletes;

    protected $table = 'expnew_activity_costs';

    protected $guarded = ['id'];
}
