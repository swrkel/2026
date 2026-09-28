<?php

namespace Modules\BankingPaymentsHub\Entities;

use Illuminate\Database\Eloquent\Model;

class PaymentReconciliation extends Model
{
    protected $table = 'bkg_payment_reconciliations';
    protected $guarded = [];
}
