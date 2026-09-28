<?php

namespace Modules\DistributionNew\Services\Sync;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\DistributionNew\Entities\DisnewSyncBatch;
use Modules\DistributionNew\Entities\DisnewSyncItem;
use Modules\DistributionNew\Entities\DisnewOfflineDevice;

class DisnewOfflineSyncService
{
    public function push(Request $request): array
    {
        $batch = DisnewSyncBatch::create([
            'business_id' => $request->input('business_id', session('business.id')),
            'location_id' => $request->input('location_id'),
            'device_uuid' => $request->input('device_uuid'),
            'batch_uuid' => $request->input('batch_uuid', (string) Str::uuid()),
            'direction' => 'push',
            'status' => 'received',
            'payload_count' => count($request->input('items', [])),
            'created_by' => auth()->id(),
        ]);

        foreach ($request->input('items', []) as $item) {
            DisnewSyncItem::create([
                'sync_batch_id' => $batch->id,
                'entity_type' => $item['entity_type'] ?? 'unknown',
                'entity_uuid' => $item['entity_uuid'] ?? null,
                'operation' => $item['operation'] ?? 'upsert',
                'payload_json' => json_encode($item['payload'] ?? []),
                'status' => 'queued',
            ]);
        }

        return ['success'=>true,'batch_id'=>$batch->id,'status'=>'received'];
    }

    public function pull(Request $request): array
    {
        return ['success'=>true,'server_time'=>now()->toDateTimeString(),'changes'=>[]];
    }

    public function batches(Request $request): array
    {
        return ['success'=>true,'data'=>DisnewSyncBatch::latest()->limit(50)->get()];
    }

    public function latestBatches(){ return DisnewSyncBatch::latest()->limit(100)->get(); }
    public function latestDevices(){ return DisnewOfflineDevice::latest()->limit(100)->get(); }
}
