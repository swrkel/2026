<?php
namespace Modules\PetroDirect\Exports;
class MeterSalesExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, ['Date', 'Pump No', 'Pump Name', 'Location', 'Opening Meter', 'Closing Meter', 'Sold Liters', 'Amount'], ['date', 'pump_no', 'pump_name', 'location_name', 'starting_meter', 'closing_meter', 'sold_ltr', 'amount']);
    }
}
