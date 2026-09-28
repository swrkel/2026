<?php

namespace Modules\ExpensesNew\Entities;

class ExpenseAttachment extends BaseModel
{
    protected $table = 'expnew_expense_attachments';
    protected $fillable = ['business_id','expense_id','file_name','file_path','file_type','file_size','created_by'];
}
