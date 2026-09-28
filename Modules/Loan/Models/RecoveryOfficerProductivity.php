<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class RecoveryOfficerProductivity extends Model
{
    protected $table = 'recovery_officer_productivity';

    protected $guarded = [];

    public function officer()
    {
        return $this->belongsTo(
            \App\User::class,
            'user_id'
        );
    }

    public function location()
    {
        return $this->belongsTo(
            \App\BusinessLocation::class,
            'location_id'
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