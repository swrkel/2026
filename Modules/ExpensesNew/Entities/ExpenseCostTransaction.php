<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCostTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'expnew_cost_transactions';

    protected $guarded = ['id'];
}
