<?php
namespace Modules\PetroDirect\Exports;
class SettlementExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, ['Settlement No', 'Date', 'Location', 'Pump Operator', 'Status', 'Total'], ['settlement_no', 'transaction_date', 'location_name', 'pump_operator_name', 'status', 'total_amount']);
    }
}
