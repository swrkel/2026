<?php

namespace Modules\BeautySaloons\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\BeautySaloons\Entities\BeautyReceptionCheckin;
use Modules\BeautySaloons\Entities\BeautyReceptionQueue;
use Modules\BeautySaloons\Entities\BeautyReceptionQueueService;

class ReceptionQueueService
{
    public function nextQueueNumber(int $businessId, ?int $locationId = null): string
    {
        $prefix = 'BQ-' . now()->format('ymd') . '-';
        $count = BeautyReceptionQueue::where('business_id', $businessId)
            ->when($locationId, fn ($q) => $q->where('business_location_id', $locationId))
            ->whereDate('created_at', today())
            ->count() + 1;

        return $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public function createQueue(array $data): BeautyReceptionQueue
    {
        return DB::transaction(function () use ($data) {
            $queue = BeautyReceptionQueue::create([
                'business_id' => $data['business_id'],
                'business_location_id' => $data['business_location_id'] ?? null,
                'queue_no' => $data['queue_no'] ?? $this->nextQueueNumber((int) $data['business_id'], $data['business_location_id'] ?? null),
                'customer_id' => $data['customer_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'visit_type' => $data['visit_type'] ?? 'walk_in',
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'waiting',
                'arrival_at' => $data['arrival_at'] ?? Carbon::now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);

            foreach (($data['services'] ?? []) as $service) {
                BeautyReceptionQueueService::create([
                    'queue_id' => $queue->id,
                    'service_id' => $service['service_id'] ?? null,
                    'service_name' => $service['service_name'] ?? null,
                    'staff_id' => $service['staff_id'] ?? null,
                    'resource_id' => $service['resource_id'] ?? null,
                    'estimated_minutes' => $service['estimated_minutes'] ?? 0,
                    'price' => $service['price'] ?? 0,
                ]);
            }

            return $queue->fresh('services');
        });
    }

    public function checkIn(BeautyReceptionQueue $queue): BeautyReceptionQueue
    {
        return DB::transaction(function () use ($queue) {
            $queue->update([
                'status' => 'checked_in',
                'check_in_at' => Carbon::now(),
                'updated_by' => auth()->id(),
            ]);

            BeautyReceptionCheckin::create([
                'business_id' => $queue->business_id,
                'business_location_id' => $queue->business_location_id,
                'queue_id' => $queue->id,
                'customer_id' => $queue->customer_id,
                'checkin_code' => 'CHK-' . $queue->queue_no,
                'checkin_at' => Carbon::now(),
                'status' => 'open',
            ]);

            return $queue->fresh();
        });
    }

    public function startService(BeautyReceptionQueue $queue, ?int $staffId = null, ?int $resourceId = null): BeautyReceptionQueue
    {
        $queue->update([
            'status' => 'in_service',
            'service_start_at' => Carbon::now(),
            'assigned_staff_id' => $staffId ?? $queue->assigned_staff_id,
            'assigned_resource_id' => $resourceId ?? $queue->assigned_resource_id,
            'updated_by' => auth()->id(),
        ]);

        return $queue->fresh();
    }

    public function complete(BeautyReceptionQueue $queue): BeautyReceptionQueue
    {
        return DB::transaction(function () use ($queue) {
            $now = Carbon::now();
            $queue->update([
                'status' => 'completed',
                'service_end_at' => $now,
                'updated_by' => auth()->id(),
            ]);

            BeautyReceptionCheckin::where('queue_id', $queue->id)
                ->where('status', 'open')
                ->update([
                    'checkout_at' => $now,
                    'status' => 'closed',
                    'waiting_minutes' => $queue->arrival_at && $queue->service_start_at ? $queue->arrival_at->diffInMinutes($queue->service_start_at) : 0,
                    'service_minutes' => $queue->service_start_at ? $queue->service_start_at->diffInMinutes($now) : 0,
                ]);

            return $queue->fresh();
        });
    }
}
