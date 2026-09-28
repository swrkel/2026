<?php

namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class DistributionFreeIssueLog extends Model
{
    protected $table = 'distribution_free_issue_logs';
    
    protected $fillable = [
        'free_issue_id',  // MAKE SURE THIS IS HERE
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'old_data',
        'new_data',
    ];
    
    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];
    
    public function user()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\User::class, 'user_id');
    }
    
    public function freeIssue()
    {
        return $this->belongsTo(DistributionFreeIssue::class, 'free_issue_id');
    }
}