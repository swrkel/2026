<?php

namespace Modules\BankingInternetBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class InternetBankingDevice extends Model
{
    protected $table = 'bkg_ib_devices';
    protected $guarded = ['id'];
}
