<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseKpiValue extends Model
{
    use SoftDeletes;

    protected $table = 'expnew_kpi_values';

    protected $guarded = ['id'];
}
