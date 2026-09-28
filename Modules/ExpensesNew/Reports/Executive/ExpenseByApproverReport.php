<?php

namespace Modules\ExpensesNew\Reports\Executive;

class ExpenseByApproverReport
{
    public string $code = 'expensebyapprover';
    public string $title = 'ExpenseByApprover Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
