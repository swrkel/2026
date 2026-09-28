<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementPaymentDetail extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_payment_details';
    protected $casts = [
        'amount' => 'decimal:4',
        'detail' => 'array',
    ];

    public function payment()
    {
        return $this->belongsTo(PdnewSettlementPayment::class, 'payment_id');
    }
}
