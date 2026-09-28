<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class PeriodCloseLine extends Model
{
    protected $table = 'stn_period_close_lines';

    protected $fillable = [
        'period_close_id','transfer_id','transfer_no','issue_type','issue_message',
        'action_required','severity','is_resolved','resolved_by','resolved_at'
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];
}
