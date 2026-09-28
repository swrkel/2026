<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\Auth;
use Modules\RestaurantNew\Entities\RestaurantNewKdsQueueItem;
use Modules\RestaurantNew\Entities\RestaurantNewKdsStatusLog;
use Modules\RestaurantNew\Entities\RestaurantNewKdsNotification;

class EnterpriseKdsService
{
    public function pushOrderItem(array $data): RestaurantNewKdsQueueItem
    {
        $data['current_status'] = $data['current_status'] ?? 'received';
        $data['priority'] = $data['priority'] ?? 'normal';
        $data['received_at'] = $data['received_at'] ?? now();
        $item = RestaurantNewKdsQueueItem::create($data);

        $this->notify($item->business_id, $item->location_id, $item->order_id, $item->id, 'new_kds_item', 'New kitchen item received', $item->item_name);
        $this->logStatus($item, null, $item->current_status, 'Item received by kitchen queue');

        return $item;
    }

    public function changeStatus(RestaurantNewKdsQueueItem $item, string $status, ?string $remarks = null): RestaurantNewKdsQueueItem
    {
        $from = $item->current_status;
        $payload = ['current_status' => $status];
        if ($status === 'accepted') $payload['accepted_at'] = now();
        if (in_array($status, ['preparing', 'cooking'], true) && empty($item->started_at)) $payload['started_at'] = now();
        if ($status === 'ready') $payload['ready_at'] = now();
        if ($status === 'collected') $payload['collected_at'] = now();
        if (in_array($status, ['served', 'delivered'], true)) $payload['served_at'] = now();

        $item->update($payload);
        $item = $item->refresh();
        $this->logStatus($item, $from, $status, $remarks);

        if ($status === 'ready') {
            $this->notify($item->business_id, $item->location_id, $item->order_id, $item->id, 'kds_item_ready', 'Kitchen item ready', $item->item_name);
        }

        return $item;
    }

    public function reassignChef(RestaurantNewKdsQueueItem $item, ?int $chefId): RestaurantNewKdsQueueItem
    {
        $item->update(['assigned_chef_id' => $chefId]);
        $this->logStatus($item, $item->current_status, $item->current_status, 'Chef assignment changed');
        return $item->refresh();
    }

    public function dashboardQueue(int $businessId, ?int $locationId = null, ?string $section = null)
    {
        return RestaurantNewKdsQueueItem::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->when($section, fn($q) => $q->where('kitchen_section', $section))
            ->whereNotIn('current_status', ['served', 'delivered', 'cancelled'])
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderBy('received_at')
            ->get();
    }

    public function timerSummary(RestaurantNewKdsQueueItem $item): array
    {
        $start = $item->started_at ?: $item->received_at ?: $item->created_at;
        $elapsed = $start ? now()->diffInMinutes($start) : 0;
        $expected = (int) $item->expected_prep_minutes;
        return [
            'elapsed_minutes' => $elapsed,
            'expected_minutes' => $expected,
            'remaining_minutes' => max(0, $expected - $elapsed),
            'timer_status' => $expected > 0 && $elapsed > $expected ? 'overdue' : ($expected > 0 && $elapsed >= max(1, $expected - 3) ? 'warning' : 'on_time'),
        ];
    }

    protected function logStatus(RestaurantNewKdsQueueItem $item, ?string $from, string $to, ?string $remarks = null): void
    {
        RestaurantNewKdsStatusLog::create([
            'business_id' => $item->business_id,
            'location_id' => $item->location_id,
            'queue_item_id' => $item->id,
            'order_id' => $item->order_id,
            'from_status' => $from,
            'to_status' => $to,
            'remarks' => $remarks,
            'changed_by' => optional(Auth::user())->id,
            'changed_at' => now(),
            'meta' => $this->timerSummary($item),
        ]);
    }

    protected function notify(int $businessId, ?int $locationId, ?int $orderId, ?int $queueItemId, string $type, string $title, ?string $message = null): void
    {
        RestaurantNewKdsNotification::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'order_id' => $orderId,
            'queue_item_id' => $queueItemId,
            'notification_type' => $type,
            'title' => $title,
            'message' => $message,
            'payload' => ['created_from' => 'restaurant_new_enterprise_kds'],
        ]);
    }
}
