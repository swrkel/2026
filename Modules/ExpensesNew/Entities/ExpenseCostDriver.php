<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCostDriver extends Model
{
    use SoftDeletes;

    protected $table = 'expnew_cost_drivers';

    protected $guarded = ['id'];
}
