<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseKpiMetric extends Model
{
    use SoftDeletes;

    protected $table = 'expnew_kpi_metrics';

    protected $guarded = ['id'];
}
