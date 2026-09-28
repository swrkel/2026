<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;

class MpcsF15DailyReport extends Model
{
    protected $table = 'mpcs_f15_daily_reports';

    protected $guarded = ['id'];

    protected $casts = [
        'report_date' => 'date',
        'prepared_date' => 'date',
        'checked_date' => 'date',
        'approved_date' => 'date',
        'changes_addition' => 'decimal:4',
        'changes_deduction' => 'decimal:4',
        'damaged' => 'decimal:4',
        'others' => 'decimal:4',
        'total_return' => 'decimal:4',
        'totals_json' => 'array',
    ];
}
