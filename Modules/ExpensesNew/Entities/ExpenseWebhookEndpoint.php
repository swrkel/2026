<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ExpenseWebhookEndpoint extends Model
{
    protected $table = 'expnew_webhook_endpoints';
    protected $guarded = ['id'];
}
