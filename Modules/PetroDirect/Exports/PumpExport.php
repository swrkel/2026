<?php
namespace Modules\PetroDirect\Exports;
class PumpExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, ['Pump No', 'Pump Name', 'Tank No', 'Location', 'Starting Meter', 'Current Meter'], ['pump_no', 'pump_name', 'fuel_tank_number', 'location_name', 'starting_meter', 'current_meter']);
    }
}
