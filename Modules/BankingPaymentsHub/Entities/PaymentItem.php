<?php

namespace Modules\BankingPaymentsHub\Entities;

use Illuminate\Database\Eloquent\Model;

class PaymentItem extends Model
{
    protected $table = 'bkg_payment_items';
    protected $guarded = [];
}
