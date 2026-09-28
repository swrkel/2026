<?php

namespace Modules\DistributionNew\Services\Sms;

use Modules\DistributionNew\Models\DisnewSmsOfficerGroup;

class SmsOfficerGroupService
{
    public function numbersForEvent(int $businessId, string $eventKey, ?int $locationId = null): array
    {
        return DisnewSmsOfficerGroup::query()
            ->where('business_id', $businessId)
            ->where('event_key', $eventKey)
            ->where('is_active', 1)
            ->when($locationId, fn ($q) => $q->where(function ($s) use ($locationId) {
                $s->whereNull('business_location_id')->orWhere('business_location_id', $locationId);
            }))
            ->get()
            ->flatMap(function ($group) {
                return array_filter(array_map('trim', explode(',', (string) $group->officer_mobile_numbers)));
            })
            ->unique()
            ->values()
            ->all();
    }
}
