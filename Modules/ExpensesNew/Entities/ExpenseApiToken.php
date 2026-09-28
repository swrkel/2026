<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ExpenseApiToken extends Model
{
    protected $table = 'expnew_api_tokens';
    protected $guarded = ['id'];
}
