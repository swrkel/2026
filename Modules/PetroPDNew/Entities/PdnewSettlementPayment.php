<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementPayment extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_payments';
    protected $casts = [
        'transaction_at' => 'datetime',
        'gross_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'amount' => 'decimal:4',
        'is_source' => 'boolean',
        'voided_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }

    public function details()
    {
        return $this->hasMany(PdnewSettlementPaymentDetail::class, 'payment_id');
    }
}
