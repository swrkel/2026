<?php

namespace Modules\LeadsNew\Models;

use Illuminate\Database\Eloquent\Model;

class LeadsNewFollowup extends Model
{
    protected $table = 'leads_new_followups';
    protected $guarded = ['id'];

    protected $casts = [
        'followup_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(LeadsNewLead::class, 'lead_id');
    }
}
