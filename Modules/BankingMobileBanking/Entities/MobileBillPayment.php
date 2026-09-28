<?php

namespace Modules\BankingMobileBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class MobileBillPayment extends Model
{
    protected $table = 'banking_mobile_bill_payments';
    protected $guarded = ['id'];
}
