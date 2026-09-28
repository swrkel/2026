<?php

namespace Modules\Subscription\Entities;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPaymentTerm extends Model
{
    protected $fillable = ['business_id','name', 'terms', 'status', 'created_by'];

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
