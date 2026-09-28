<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ExpenseIntegrationPosting extends Model
{
    protected $table = 'expnew_integration_postings';
    protected $guarded = ['id'];
}
