<?php
namespace Modules\AirlineTicketingNew\Services\Workflow;

use Modules\AirlineTicketingNew\Entities\ApprovalMatrix;

class ApprovalMatrixResolver
{
    public function resolve(int $businessId, string $eventCode, float $amount = 0, ?int $locationId = null)
    {
        return ApprovalMatrix::query()
            ->where('business_id', $businessId)
            ->where('event_code', $eventCode)
            ->where('is_active', true)
            ->when($locationId, fn ($q) => $q->where(function ($x) use ($locationId) {
                $x->whereNull('business_location_id')->orWhere('business_location_id', $locationId);
            }))
            ->where('minimum_amount', '<=', $amount)
            ->where(function ($q) use ($amount) {
                $q->whereNull('maximum_amount')->orWhere('maximum_amount', '>=', $amount);
            })
            ->orderBy('approval_level')
            ->get();
    }
}
