<?php

namespace Modules\LeadsNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadsNewLead extends Model
{
    use SoftDeletes;

    protected $table = 'leads_new_leads';

    protected $guarded = ['id'];

    protected $casts = [
        'transaction_date' => 'date',
        'next_followup_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    public function followups()
    {
        return $this->hasMany(LeadsNewFollowup::class, 'lead_id');
    }
}
