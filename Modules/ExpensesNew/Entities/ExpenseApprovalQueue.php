<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ExpenseApprovalQueue extends Model
{
    protected $table = 'expnew_approval_queues';
    protected $guarded = [];
}
