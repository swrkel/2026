<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewSettlementMeterSale;

class MeterSalesReport extends AbstractReport
{
    public function key(): string { return 'meter_sales'; }
    public function label(): string { return 'Meter Sales'; }
    protected function modelClass(): string { return PdirectnewSettlementMeterSale::class; }
}
