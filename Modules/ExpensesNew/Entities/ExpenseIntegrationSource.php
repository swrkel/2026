<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ExpenseIntegrationSource extends Model
{
    protected $table = 'expnew_integration_sources';
    protected $guarded = ['id'];
}
