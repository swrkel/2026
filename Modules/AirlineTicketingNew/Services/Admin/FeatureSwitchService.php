<?php
namespace Modules\AirlineTicketingNew\Services\Admin;

use Modules\AirlineTicketingNew\Entities\FeatureSwitch;

class FeatureSwitchService
{
    public function enabled(int $businessId, string $featureCode): bool
    {
        return (bool) FeatureSwitch::query()
            ->where('business_id', $businessId)
            ->where('feature_code', $featureCode)
            ->value('is_enabled');
    }
}
