<?php

namespace Modules\BankingInternetBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class InternetBankingSecureMessage extends Model
{
    protected $table = 'bkg_ib_secure_messages';
    protected $guarded = ['id'];
}
