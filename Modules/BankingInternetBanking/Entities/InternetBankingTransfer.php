<?php

namespace Modules\BankingInternetBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class InternetBankingTransfer extends Model
{
    protected $table = 'bkg_ib_transfers';
    protected $guarded = ['id'];
}
