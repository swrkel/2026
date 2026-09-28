<?php
namespace Modules\PetroDirect\Exports;
class VehicleExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, ['Vehicle No', 'Vehicle Name', 'Created At'], ['vehicle_no', 'vehicle_name', 'created_at']);
    }
}
