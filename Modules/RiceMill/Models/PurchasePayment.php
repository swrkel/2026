<?php
namespace Modules\RiceMill\Models;

class PurchasePayment extends BaseRiceMillModel
{
    protected $table = 'rcm_purchase_payments';
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:4',
    ];
}
