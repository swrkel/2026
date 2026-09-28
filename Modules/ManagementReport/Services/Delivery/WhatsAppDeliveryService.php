<?php

namespace Modules\ManagementReport\Services\Delivery;

use App\Services\Messaging\GlobalWhatsAppService;

/**
 * Compatibility adapter. Management Report now uses the application-wide
 * WhatsApp service and no longer maintains a separate provider/version.
 */
class WhatsAppDeliveryService
{
    public function send($businessId, array $recipients, $message)
    {
        return app(GlobalWhatsAppService::class)->send($recipients, $message, [
            'source_module' => 'ManagementReport',
            'source_reference' => 'management_report_share',
            'subject' => 'Management Report',
            'context' => [
                'business_id' => $businessId,
                'page_title' => 'Management Report',
                'page_no' => 1,
            ],
        ]);
    }
}
