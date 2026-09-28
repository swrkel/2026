<?php

namespace Modules\ExpensesNew\Entities;

/**
 * IS1991 (#1): one saved expense form prefix.
 *
 * The row that backs the Prefix List on Expenses-New > Settings. The prefix
 * currently in use for generating codes still lives in expnew_settings under
 * `category_code_prefix`; this is the record of what was saved, by whom and
 * when.
 */
class ExpensePrefix extends BaseModel
{
    protected $table = 'expnew_expense_prefixes';
}
