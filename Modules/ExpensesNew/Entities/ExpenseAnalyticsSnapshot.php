<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ExpenseAnalyticsSnapshot extends Model
{
    /**
     * Resolve the staged EXPNEW table name at runtime. PHP properties may not
     * call helper functions in a constant expression.
     */
    public function getTable()
    {
        return 'expnew_' . strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', class_basename(static::class)));
    }
    protected $guarded = ['id'];
}
