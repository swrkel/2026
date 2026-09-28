<?php
namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewMobileDevice;
use Modules\DistributionNew\Models\DisnewOfflineQueue;
use Modules\DistributionNew\Models\DisnewSyncConflict;

class DisnewMobileSyncService
{
    public function registerDevice(array $payload): DisnewMobileDevice
    {
        return DisnewMobileDevice::updateOrCreate(
            ['business_id' => $payload['business_id'], 'device_uid' => $payload['device_uid']],
            $payload + ['is_active' => 1, 'registered_at' => now()]
        );
    }

    public function pushOfflineQueue(array $header, array $items): array
    {
        return DB::transaction(function () use ($header, $items) {
            $saved = [];
            foreach ($items as $item) {
                $saved[] = DisnewOfflineQueue::create([
                    'business_id' => $header['business_id'],
                    'location_id' => $header['location_id'] ?? null,
                    'user_id' => $header['user_id'] ?? null,
                    'device_id' => $header['device_id'] ?? null,
                    'entity_type' => $item['entity_type'],
                    'entity_local_id' => $item['entity_local_id'] ?? null,
                    'entity_server_id' => $item['entity_server_id'] ?? null,
                    'payload_json' => json_encode($item['payload'] ?? []),
                    'sync_status' => 'pending',
                ]);
            }
            return $saved;
        });
    }

    public function markConflict(array $payload): DisnewSyncConflict
    {
        return DisnewSyncConflict::create($payload + ['resolution_status' => 'open']);
    }
}
