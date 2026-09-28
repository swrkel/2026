<?php

namespace Modules\Subscription\Entities;

use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoicePrefix extends Model
{
    protected $fillable = ['user_id','business_id', 'prefix', 'current_number', 'created_by'];

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }
}
