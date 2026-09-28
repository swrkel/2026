<?php

namespace Modules\PetroGeneral\Services\TankTransfer;

use App\BusinessLocation;
use App\Product;
use Modules\PetroGeneral\Entities\FuelTank;

class TankTransferPageService
{
    public function getIndexData(int $businessId): array
    {
        return [
            'business_locations' => BusinessLocation::forDropdown($businessId),
            'tank_numbers' => FuelTank::where('business_id', $businessId)->pluck('fuel_tank_number', 'id'),
            'products' => Product::where('business_id', $businessId)->pluck('name', 'id'),
        ];
    }
}
