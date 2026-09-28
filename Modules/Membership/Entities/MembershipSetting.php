<?php

namespace Modules\Membership\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;

class MembershipSetting extends Model
{
    protected $fillable = ['business_id', 'region', 'prefix', 'starting_number', 'next_sequence', 'created_by'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
