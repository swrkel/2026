<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LeadsNewWebhookService
{
    public function dispatch(int $businessId, string $event, array $payload): void
    {
        $hooks = DB::table('leads_new_webhooks')
            ->where('business_id', $businessId)
            ->where('event_name', $event)
            ->where('is_active', 1)
            ->get();

        foreach ($hooks as $hook) {
            try {
                Http::timeout(10)->post($hook->target_url, $payload);
                $status = 'sent';
            } catch (\Throwable $e) {
                $status = 'failed: '.$e->getMessage();
            }

            DB::table('leads_new_webhook_logs')->insert([
                'business_id' => $businessId,
                'webhook_id' => $hook->id,
                'event_name' => $event,
                'status' => $status,
                'payload' => json_encode($payload),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
