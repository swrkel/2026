<?php

namespace Modules\SettlementSW\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SettlementSW\Entities\Concerns\RequiresReconcilerContext;

class SettlementSwVatSettlementCashPayment extends Model
{
    use RequiresReconcilerContext;

    protected $table = 'vat_settlement_cash_payments';
    protected $guarded = ['id'];
}
