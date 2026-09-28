<?php

namespace Modules\DistributionNew\Services\Sms;

use Illuminate\Support\Facades\DB;

class DisnewSmsEventService
{
    public function __construct(
        protected DisnewSmsTemplateService $templates,
        protected DisnewExistingSmsModuleBridge $bridge
    ) {}

    public function fire(int $businessId, ?int $locationId, string $event, ?int $customerId, ?string $customerMobile, array $data): void
    {
        $rendered = $this->templates->render($businessId, $event, $data);

        if ($rendered['send_to_customer'] && $customerMobile) {
            $this->insertAndBridge($businessId, $locationId, $event, $rendered['template_id'], $customerId, null, $customerMobile, $rendered['message'], $data);
        }

        if ($rendered['send_to_officers']) {
            $officers = DB::table('disnew_sms_officers')->where('business_id', $businessId)->where('is_active', 1)->get();
            foreach ($officers as $officer) {
                $this->insertAndBridge($businessId, $locationId, $event, $rendered['template_id'], null, $officer->name, $officer->mobile, $rendered['message'], $data);
            }
        }
    }

    private function insertAndBridge(int $businessId, ?int $locationId, string $event, ?int $templateId, ?int $customerId, ?string $officerName, string $mobile, string $message, array $payload): void
    {
        $id = DB::table('disnew_sms_logs')->insertGetId([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'event' => $event,
            'template_id' => $templateId,
            'customer_id' => $customerId,
            'officer_name' => $officerName,
            'mobile' => $mobile,
            'message' => $message,
            'payload_json' => json_encode($payload),
            'status' => 'queued',
            'bridge_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $log = DB::table('disnew_sms_logs')->where('id', $id)->first();
        $this->bridge->pushQueuedLog($log);
    }
}
