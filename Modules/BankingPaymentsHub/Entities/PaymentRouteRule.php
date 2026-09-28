<?php

namespace Modules\BankingPaymentsHub\Entities;

use Illuminate\Database\Eloquent\Model;

class PaymentRouteRule extends Model
{
    protected $table = 'bkg_payment_routes';
    protected $guarded = [];
}
