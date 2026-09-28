<?php

namespace Modules\BankingPaymentsHub\Entities;

use Illuminate\Database\Eloquent\Model;

class PaymentQueueItem extends Model
{
    protected $table = 'bkg_payment_queue';
    protected $guarded = [];
}
