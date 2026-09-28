<?php
namespace Modules\Subscription\Entities;
use Illuminate\Database\Eloquent\Model;
class EstateSubscriptionReminderLog extends Model
{
    protected $table = 'subs_reminder_logs';
    protected $guarded = ['id'];
}
