<?php
namespace Modules\AirlineTicketingNew\Services\Suppliers;

use Modules\AirlineTicketingNew\Entities\SupplierServiceAgreement;

class SupplierServiceResolver
{
    public function resolve(int $businessId, string $serviceType, ?int $locationId = null)
    {
        return SupplierServiceAgreement::query()
            ->where('business_id', $businessId)
            ->where('service_type', $serviceType)
            ->where('is_active', true)
            ->when($locationId, fn ($q) => $q->where(function ($x) use ($locationId) {
                $x->whereNull('business_location_id')->orWhere('business_location_id', $locationId);
            }))
            ->whereDate('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', now());
            })
            ->orderByDesc('priority')
            ->first();
    }
}
