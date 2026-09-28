<?php

namespace Modules\MyAuto\Entities;

use Illuminate\Database\Eloquent\Model;

class MyAutoSubscription extends Model
{
    protected $table = 'my_auto_subscriptions';

    protected $fillable = [
        'user_id',
        'period',
        'amount',
        'payment_mode',
        'payment_status',
        'transaction_id',
        'starts_at',
        'ends_at'
    ];

}
