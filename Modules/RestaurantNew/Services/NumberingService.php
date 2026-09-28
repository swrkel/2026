<?php

namespace Modules\RestaurantNew\Services;

class NumberingService
{
    public function next(string $type, int $businessId, ?int $locationId = null): string
    {
        $prefix = strtoupper(substr($type, 0, 3));
        return $prefix . '-' . $businessId . ($locationId ? '-' . $locationId : '') . '-' . now()->format('YmdHis');
    }
}
