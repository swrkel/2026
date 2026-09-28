<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ExpenseWebhookDelivery extends Model
{
    protected $table = 'expnew_webhook_deliveries';
    protected $guarded = ['id'];
}
