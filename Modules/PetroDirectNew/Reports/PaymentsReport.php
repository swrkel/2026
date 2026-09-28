<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewSettlementPayment;

class PaymentsReport extends AbstractReport
{
    public function key(): string { return 'payments'; }
    public function label(): string { return 'Settlement Payments'; }
    protected function modelClass(): string { return PdirectnewSettlementPayment::class; }
}
