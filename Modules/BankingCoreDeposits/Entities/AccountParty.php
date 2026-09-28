<?php

namespace Modules\BankingCoreDeposits\Entities;

use Illuminate\Database\Eloquent\Model;

class AccountParty extends Model
{
    protected $table = 'bkg_core_account_parties';
    protected $guarded = ['id'];
    protected $casts = ['rules' => 'array', 'extra' => 'array', 'renewal_history' => 'array', 'last_error' => 'array', 'approved_at' => 'datetime', 'posted_at' => 'datetime'];
}
