<?php
namespace Modules\Subscription\Entities;
use Illuminate\Database\Eloquent\Model;
class EstateSubscription extends Model
{
    protected $table = 'subs_business_subscriptions';
    protected $guarded = ['id'];
    protected $casts = ['business_registered_on' => 'date', 'expiry_date' => 'date'];
    public function reminders() { return $this->hasMany(EstateSubscriptionReminder::class, 'subscription_id')->orderBy('reminder_no'); }
}
