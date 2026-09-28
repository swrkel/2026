<?php

namespace Modules\PumperDashboardNew\Entities;

class PonePaymentCashDenomination extends PoneBaseModel
{
    protected $table = 'pone_payment_cash_denominations';
    protected $casts = ['denomination' => 'decimal:2', 'amount' => 'decimal:4'];
    public function payment() { return $this->belongsTo(PonePayment::class, 'payment_id'); }
}
