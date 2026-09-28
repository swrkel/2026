<?php

namespace Modules\ExpensesNew\Entities;

class ExpenseAccount extends BaseModel
{
    protected $table = 'expnew_expense_accounts';
    protected $guarded = ['id'];
}
