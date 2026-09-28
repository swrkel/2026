<?php

namespace Modules\BankingInternetBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class InternetBankingBillPayment extends Model
{
    protected $table = 'bkg_ib_bill_payments';
    protected $guarded = ['id'];
}
