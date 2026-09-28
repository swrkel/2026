<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewSettlementPayment extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_settlement_payments';
    protected $casts = [
        'metadata' => 'array',
        'details' => 'array',
        'filters' => 'array',
    ];
}
