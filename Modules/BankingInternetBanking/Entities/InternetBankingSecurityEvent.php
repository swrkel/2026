<?php

namespace Modules\BankingInternetBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class InternetBankingSecurityEvent extends Model
{
    protected $table = 'bkg_ib_security_events';
    protected $guarded = ['id'];
}
