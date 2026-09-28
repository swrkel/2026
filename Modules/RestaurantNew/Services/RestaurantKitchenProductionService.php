<?php

namespace Modules\RestaurantNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenQueue;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenTimer;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenPerformanceLog;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenRouteRule;

class RestaurantKitchenProductionService
{
    public function createQueueForOrderItem(array $data): RestaurantNewKitchenQueue
    {
        return DB::transaction(function () use ($data) {
            $sectionId = $data['kitchen_section_id'] ?? $this->resolveKitchenSection($data);
            $queue = RestaurantNewKitchenQueue::create([
                'business_id' => $data['business_id'],
                'location_id' => $data['location_id'],
                'kitchen_section_id' => $sectionId,
                'order_id' => $data['order_id'],
                'order_item_id' => $data['order_item_id'] ?? null,
                'kot_id' => $data['kot_id'] ?? null,
                'queue_no' => $data['queue_no'] ?? $this->nextQueueNo($data['business_id'], $data['location_id']),
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'received',
                'received_at' => Carbon::now(),
                'estimated_minutes' => $data['estimated_minutes'] ?? 15,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);

            $this->startTimer($queue, 'received');
            return $queue;
        });
    }

    public function markPreparing(RestaurantNewKitchenQueue $queue): RestaurantNewKitchenQueue
    {
        $queue->update(['status' => 'preparing', 'started_at' => Carbon::now(), 'updated_by' => auth()->id()]);
        $this->startTimer($queue, 'preparing');
        return $queue->fresh();
    }

    public function markReady(RestaurantNewKitchenQueue $queue): RestaurantNewKitchenQueue
    {
        $readyAt = Carbon::now();
        $actual = $queue->started_at ? $queue->started_at->diffInMinutes($readyAt) : $queue->received_at->diffInMinutes($readyAt);
        $queue->update(['status' => 'ready', 'ready_at' => $readyAt, 'actual_minutes' => $actual, 'updated_by' => auth()->id()]);
        $this->closeOpenTimers($queue);
        $this->writePerformanceLog($queue, 'ready');
        return $queue->fresh();
    }

    public function markServed(RestaurantNewKitchenQueue $queue): RestaurantNewKitchenQueue
    {
        $queue->update(['status' => 'served', 'served_at' => Carbon::now(), 'updated_by' => auth()->id()]);
        $this->writePerformanceLog($queue, 'served');
        return $queue->fresh();
    }

    public function changePriority(RestaurantNewKitchenQueue $queue, string $priority, ?string $notes = null): RestaurantNewKitchenQueue
    {
        $queue->update(['priority' => $priority, 'notes' => $notes ?: $queue->notes, 'updated_by' => auth()->id()]);
        return $queue->fresh();
    }

    public function boardData(int $businessId, ?int $locationId = null, ?int $sectionId = null)
    {
        return RestaurantNewKitchenQueue::query()
            ->where('business_id', $businessId)
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->when($sectionId, fn ($q) => $q->where('kitchen_section_id', $sectionId))
            ->whereIn('status', ['received','preparing','ready'])
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderBy('received_at')
            ->get();
    }

    protected function resolveKitchenSection(array $data): ?int
    {
        $rule = RestaurantNewKitchenRouteRule::where('business_id', $data['business_id'])
            ->where('location_id', $data['location_id'])
            ->where('is_active', 1)
            ->when($data['menu_item_id'] ?? null, fn ($q, $id) => $q->where('menu_item_id', $id))
            ->when($data['menu_category_id'] ?? null, fn ($q, $id) => $q->orWhere('menu_category_id', $id))
            ->orderBy('priority')
            ->first();

        return $rule?->kitchen_section_id;
    }

    protected function nextQueueNo(int $businessId, int $locationId): string
    {
        $count = RestaurantNewKitchenQueue::where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->whereDate('created_at', Carbon::today())
            ->count() + 1;
        return 'KQ-' . Carbon::now()->format('ymd') . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function startTimer(RestaurantNewKitchenQueue $queue, string $type): void
    {
        RestaurantNewKitchenTimer::create([
            'business_id' => $queue->business_id,
            'location_id' => $queue->location_id,
            'queue_id' => $queue->id,
            'order_id' => $queue->order_id,
            'order_item_id' => $queue->order_item_id,
            'timer_type' => $type,
            'started_at' => Carbon::now(),
            'status' => 'running',
            'created_by' => auth()->id(),
        ]);
    }

    protected function closeOpenTimers(RestaurantNewKitchenQueue $queue): void
    {
        RestaurantNewKitchenTimer::where('queue_id', $queue->id)->where('status', 'running')
            ->get()->each(function ($timer) {
                $endedAt = Carbon::now();
                $timer->update([
                    'ended_at' => $endedAt,
                    'total_seconds' => $timer->started_at ? $timer->started_at->diffInSeconds($endedAt) : 0,
                    'status' => 'ended',
                ]);
            });
    }

    protected function writePerformanceLog(RestaurantNewKitchenQueue $queue, string $status): void
    {
        RestaurantNewKitchenPerformanceLog::create([
            'business_id' => $queue->business_id,
            'location_id' => $queue->location_id,
            'kitchen_section_id' => $queue->kitchen_section_id,
            'queue_id' => $queue->id,
            'order_id' => $queue->order_id,
            'order_item_id' => $queue->order_item_id,
            'metric_date' => Carbon::today()->toDateString(),
            'target_minutes' => $queue->estimated_minutes,
            'actual_minutes' => $queue->actual_minutes,
            'delay_minutes' => max(0, (int) $queue->actual_minutes - (int) $queue->estimated_minutes),
            'status' => $status,
        ]);
    }
}
