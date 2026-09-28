<?php

namespace Modules\BankingCoreDeposits\Entities;

use Illuminate\Database\Eloquent\Model;

class FixedDeposit extends Model
{
    protected $table = 'bkg_core_fixed_deposits';
    protected $guarded = ['id'];
    protected $casts = ['rules' => 'array', 'extra' => 'array', 'renewal_history' => 'array', 'last_error' => 'array', 'approved_at' => 'datetime', 'posted_at' => 'datetime'];
}
