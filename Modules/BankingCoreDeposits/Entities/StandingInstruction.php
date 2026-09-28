<?php

namespace Modules\BankingCoreDeposits\Entities;

use Illuminate\Database\Eloquent\Model;

class StandingInstruction extends Model
{
    protected $table = 'bkg_core_standing_instructions';
    protected $guarded = ['id'];
    protected $casts = ['rules' => 'array', 'extra' => 'array', 'renewal_history' => 'array', 'last_error' => 'array', 'approved_at' => 'datetime', 'posted_at' => 'datetime'];
}
