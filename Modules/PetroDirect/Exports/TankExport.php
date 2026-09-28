<?php
namespace Modules\PetroDirect\Exports;
class TankExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, ['Tank No', 'Manufacturer', 'Product', 'Location', 'Capacity', 'Current Balance'], ['fuel_tank_number', 'tank_manufacturer', 'product_name', 'location_name', 'storage_volume', 'current_balance']);
    }
}
