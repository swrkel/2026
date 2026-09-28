<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;

class EnterpriseNotification extends Model
{
    protected $table = 'enterprise_notifications';

    protected $guarded = [];

    public function location()
    {
        return $this->belongsTo(
            \App\BusinessLocation::class,
            'location_id'
        );
    }

    public function assignedTo()
    {
        return $this->belongsTo(
            \App\User::class,
            'assigned_to'
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'created_by'
        );
    }
}