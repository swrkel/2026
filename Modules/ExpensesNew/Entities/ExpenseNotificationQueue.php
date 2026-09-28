<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ExpenseNotificationQueue extends Model
{
    protected $table = 'expnew_notification_queue';
    protected $guarded = ['id'];
}
