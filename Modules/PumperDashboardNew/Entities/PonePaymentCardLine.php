<?php

namespace Modules\PumperDashboardNew\Entities;

class PonePaymentCardLine extends PoneBaseModel
{
    protected $table = 'pone_payment_card_lines';
    protected $casts = ['amount' => 'decimal:4'];
    public function payment() { return $this->belongsTo(PonePayment::class, 'payment_id'); }
}
