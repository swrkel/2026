<?php
namespace Modules\PetroDirect\Exports;
class ShiftExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, ['Shift No', 'Date', 'Location', 'Pump Operator', 'Status'], ['shift_no', 'date', 'location_name', 'pump_operator_name', 'status']);
    }
}
