<?php

namespace Modules\Subscription\Entities;

use Illuminate\Database\Eloquent\Model;

class SubscriptionBanner extends Model
{
    protected $fillable = ['business_id','file_path', 'width', 'height', 'created_by'];

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
