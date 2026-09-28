<?php

namespace Modules\BankingInternetBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class InternetBankingCustomer extends Model
{
    protected $table = 'bkg_ib_customers';
    protected $guarded = ['id'];
}
