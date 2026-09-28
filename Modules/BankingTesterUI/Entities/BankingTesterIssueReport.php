<?php

namespace Modules\BankingTesterUI\Entities;

use Illuminate\Database\Eloquent\Model;

class BankingTesterIssueReport extends Model
{
    protected $table = 'banking_tester_issue_reports';

    protected $guarded = ['id'];

    protected $casts = [
        'fixed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];
}
