<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;

class FinanceNotification extends Model
{
    protected $table = 'finance_notifications';

    protected $guarded = [];

    public function location()
    {
        return $this->belongsTo(
            \App\BusinessLocation::class,
            'location_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            \App\User::class,
            'user_id'
        );
    }
}