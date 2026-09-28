<?php
namespace Modules\Subscription\Entities;
use Illuminate\Database\Eloquent\Model;
class EstateSubscriptionReminder extends Model
{
    protected $table = 'subs_subscription_reminders';
    protected $guarded = ['id'];
}
