<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ExpenseNotificationTemplate extends Model
{
    protected $table = 'expnew_notification_templates';
    protected $guarded = ['id'];
}
