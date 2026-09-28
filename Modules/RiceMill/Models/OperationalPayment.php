<?php
namespace Modules\RiceMill\Models;

class OperationalPayment extends BaseRiceMillModel
{
    protected $table = 'rcm_operational_payments';

    protected $casts = [
        'amount' => 'decimal:4',
        'meta' => 'array',
        'posted_at' => 'datetime',
    ];
}
