<?php
namespace Modules\AirlineTicketingNew\Services\Bundles;

use Modules\AirlineTicketingNew\Entities\ServiceBundle;

class ServiceBundlePricingService
{
    public function summary(ServiceBundle $bundle): array
    {
        $bundle->load('items');

        $itemTotal = $bundle->items->sum(
            fn ($item) => (float) $item->quantity * (float) $item->unit_price
        );

        return [
            'item_total' => round($itemTotal, 4),
            'bundle_price' => round((float) $bundle->bundle_price, 4),
            'saving' => round(max(0, $itemTotal - (float) $bundle->bundle_price), 4),
        ];
    }
}
