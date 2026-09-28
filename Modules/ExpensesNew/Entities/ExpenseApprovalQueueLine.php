<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ExpenseApprovalQueueLine extends Model
{
    protected $table = 'expnew_approval_queue_line';
    protected $guarded = [];
}
