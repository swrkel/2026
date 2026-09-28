<?php

namespace Modules\DistributionNew\Services\Logistics;

use Carbon\Carbon;
use Modules\DistributionNew\Models\DisnewVehicleDocument;
use Modules\DistributionNew\Models\DisnewDriver;

class DisnewVehicleDocumentExpiryService
{
    public function expiringDocuments(int $businessId, int $days = 30)
    {
        return DisnewVehicleDocument::forBusiness($businessId)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', Carbon::today()->addDays($days))
            ->orderBy('expiry_date')
            ->get();
    }

    public function expiringDriverLicenses(int $businessId, int $days = 30)
    {
        return DisnewDriver::forBusiness($businessId)
            ->where('status', 'active')
            ->whereNotNull('license_expiry_date')
            ->whereDate('license_expiry_date', '<=', Carbon::today()->addDays($days))
            ->orderBy('license_expiry_date')
            ->get();
    }
}
