<?php
namespace Modules\ManagementReport\Services\Delivery;
use Modules\ManagementReport\Support\TenantConnection;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmsDeliveryService
{
    public function send($businessId, array $recipients, $message)
    {
        $queue = config('managementreport.queue_table', 'communication_hub_messages');
        if (TenantConnection::schema()->hasTable($queue)) {
            $columns = TenantConnection::schema()->getColumnListing($queue);
            foreach ($recipients as $recipient) {
                $payload = [
                    'business_id' => $businessId,
                    'module' => 'ManagementReport',
                    'source_module' => 'ManagementReport',
                    'source_reference' => 'management_report_share',
                    'channel' => 'sms',
                    'recipient' => $recipient,
                    'subject' => 'Management Report',
                    'body' => $message,
                    'message' => $message,
                    'payload' => json_encode(['source' => 'management_report']),
                    'priority' => 'normal',
                    'status' => 'pending',
                    'attempts' => 0,
                    'retries' => 0,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                TenantConnection::db()->table($queue)->insert(array_intersect_key($payload, array_flip($columns)));
            }
            return ['status' => 'queued', 'provider' => 'communication_hub'];
        }

        if (!TenantConnection::schema()->hasTable('business')) {
            throw new RuntimeException('SMS settings are unavailable.');
        }

        $business = TenantConnection::db()->table('business')->where('id', $businessId)->first();
        $settings = json_decode(optional($business)->sms_settings ?: '{}', true);
        $url = $settings['url'] ?? $settings['gateway_url'] ?? null;
        if (!$url) {
            throw new RuntimeException('Please configure an SMS gateway or enable Communication Hub.');
        }

        foreach ($recipients as $recipient) {
            Http::asForm()->timeout(20)->post($url, array_merge($settings['parameters'] ?? [], ['to' => $recipient, 'message' => $message]))->throw();
        }
        return ['status' => 'sent', 'provider' => 'business_sms_gateway'];
    }
}
