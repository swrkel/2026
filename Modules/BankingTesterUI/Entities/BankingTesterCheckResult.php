<?php

namespace Modules\BankingTesterUI\Entities;

use Illuminate\Database\Eloquent\Model;

class BankingTesterCheckResult extends Model
{
    protected $table = 'banking_tester_ui_check_results';

    protected $guarded = ['id'];

    protected $casts = [
        'checked_at' => 'datetime',
    ];
}
