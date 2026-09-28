<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseActivity extends Model
{
    use SoftDeletes;

    protected $table = 'expnew_activitys';

    protected $guarded = ['id'];
}
