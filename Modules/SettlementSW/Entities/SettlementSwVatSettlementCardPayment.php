<?php

namespace Modules\SettlementSW\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SettlementSW\Entities\Concerns\RequiresReconcilerContext;

class SettlementSwVatSettlementCardPayment extends Model
{
    use RequiresReconcilerContext;

    protected $table = 'vat_settlement_card_payments';
    protected $guarded = ['id'];
}
