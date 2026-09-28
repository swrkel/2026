<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\RestaurantNew\Entities\RestaurantNewTableServiceRequest;
use Modules\RestaurantNew\Entities\RestaurantNewCustomerOrderTracking;
use Modules\RestaurantNew\Entities\RestaurantNewCustomerExperienceLog;

class CustomerExperienceService
{
    public function openRequest(array $data): RestaurantNewTableServiceRequest
    {
        $data['request_no'] = $data['request_no'] ?? $this->nextRequestNo($data['business_id']);
        $data['status'] = $data['status'] ?? 'open';
        $data['created_by'] = $data['created_by'] ?? optional(Auth::user())->id;

        $request = RestaurantNewTableServiceRequest::create($data);
        $this->log($data['business_id'], $data['location_id'] ?? null, $data['order_id'] ?? null, $data['table_id'] ?? null, 'service_request_opened', 'Customer service request opened', ['request_id' => $request->id]);

        return $request;
    }

    public function changeRequestStatus(RestaurantNewTableServiceRequest $request, string $status, ?string $remarks = null): RestaurantNewTableServiceRequest
    {
        $payload = ['status' => $status, 'updated_by' => optional(Auth::user())->id];
        if ($status === 'acknowledged') {
            $payload['acknowledged_at'] = now();
        }
        if (in_array($status, ['completed', 'cancelled'], true)) {
            $payload['completed_at'] = now();
        }

        $request->update($payload);
        $this->log($request->business_id, $request->location_id, $request->order_id, $request->table_id, 'service_request_'.$status, $remarks ?: 'Service request status changed', ['request_id' => $request->id]);

        return $request->refresh();
    }

    public function createTracking(int $businessId, ?int $locationId, int $orderId, ?string $mobile = null): RestaurantNewCustomerOrderTracking
    {
        return RestaurantNewCustomerOrderTracking::firstOrCreate(
            ['business_id' => $businessId, 'order_id' => $orderId],
            [
                'location_id' => $locationId,
                'tracking_token' => Str::random(40),
                'customer_mobile' => $mobile,
                'current_status' => 'received',
                'last_status_at' => now(),
                'is_active' => true,
            ]
        );
    }

    public function updateTrackingStatus(int $orderId, string $status, array $payload = []): void
    {
        RestaurantNewCustomerOrderTracking::where('order_id', $orderId)->update([
            'current_status' => $status,
            'last_status_at' => now(),
            'public_payload' => $payload,
        ]);
    }

    public function log(int $businessId, ?int $locationId, ?int $orderId, ?int $tableId, string $eventType, ?string $description = null, array $meta = []): void
    {
        RestaurantNewCustomerExperienceLog::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'order_id' => $orderId,
            'table_id' => $tableId,
            'event_type' => $eventType,
            'description' => $description,
            'meta' => $meta,
            'created_by' => optional(Auth::user())->id,
        ]);
    }

    protected function nextRequestNo(int $businessId): string
    {
        $next = RestaurantNewTableServiceRequest::where('business_id', $businessId)->count() + 1;
        return 'RSR-' . date('Ymd') . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
