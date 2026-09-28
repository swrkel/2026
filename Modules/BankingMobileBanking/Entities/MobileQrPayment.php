<?php

namespace Modules\BankingMobileBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class MobileQrPayment extends Model
{
    protected $table = 'banking_mobile_qr_payments';
    protected $guarded = ['id'];
}
