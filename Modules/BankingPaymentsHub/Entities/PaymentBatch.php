<?php

namespace Modules\BankingPaymentsHub\Entities;

use Illuminate\Database\Eloquent\Model;

class PaymentBatch extends Model
{
    protected $table = 'bkg_payment_batches';
    protected $guarded = [];
}
