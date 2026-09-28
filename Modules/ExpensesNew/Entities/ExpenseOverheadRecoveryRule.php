<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseOverheadRecoveryRule extends Model
{
    use SoftDeletes;

    protected $table = 'expnew_overhead_recovery_rules';

    protected $guarded = ['id'];
}
