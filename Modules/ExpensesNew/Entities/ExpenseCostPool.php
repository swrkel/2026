<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCostPool extends Model
{
    use SoftDeletes;

    protected $table = 'expnew_cost_pools';

    protected $guarded = ['id'];
}
