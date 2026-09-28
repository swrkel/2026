<?php

namespace Modules\Subscription\Entities;

use Illuminate\Database\Eloquent\Model;

class SubscriptionBankAccount extends Model
{
    protected $fillable = [
        'business_id',
        'template_name',
        'ac_name',
        'ac_no',
        'bank',
        'branch',
        'status',
        'created_by'
    ];

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
