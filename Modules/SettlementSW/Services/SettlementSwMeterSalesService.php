<?php

namespace Modules\SettlementSW\Services;

/** SW_SEP_003 meter sales service. */
class SettlementSwMeterSalesService extends SettlementSwBaseService
{
    public function calculateLineTotal($qty, $unitPrice): float
    {
        return $this->money((float) $qty * (float) $unitPrice);
    }

    public function totalSales($meterSales): float
    {
        return $this->sumAmount($meterSales, 'sub_total');
    }
}
