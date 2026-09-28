<?php

namespace Modules\ExpensesNew\Entities;

class Category extends BaseModel
{
    protected $table = 'expnew_categories';

    public function expenseAccount()
    {
        return $this->belongsTo(ExpenseAccount::class, 'expense_account_id');
    }

    public function defaultPayee()
    {
        return $this->belongsTo(Payee::class, 'default_payee_id');
    }
}
