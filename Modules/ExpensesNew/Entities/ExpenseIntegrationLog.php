<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ExpenseIntegrationLog extends Model
{
    protected $table = 'expnew_integration_logs';
    protected $guarded = ['id'];
}
