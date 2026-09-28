<?php

namespace Modules\ExpensesNew\Entities;

class ExpensePayment extends BaseModel
{
    protected $table = 'expnew_expense_payments';
    protected $guarded = ['id'];

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }
}
