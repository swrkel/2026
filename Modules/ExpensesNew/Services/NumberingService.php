<?php

namespace Modules\ExpensesNew\Services;

use Modules\ExpensesNew\Entities\Expense;

class NumberingService
{
    public function nextExpenseNo(int $businessId): string
    {
        $prefix = 'EXN-';
        $last = Expense::where('business_id', $businessId)->max('id') ?: 0;
        return $prefix . str_pad((string)($last + 1), 6, '0', STR_PAD_LEFT);
    }
}
