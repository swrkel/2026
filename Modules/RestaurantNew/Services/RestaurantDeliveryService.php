<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewCustomerAddress;
use Modules\RestaurantNew\Entities\RestaurantNewDeliveryOrder;
use Modules\RestaurantNew\Entities\RestaurantNewDeliveryRider;
use Modules\RestaurantNew\Entities\RestaurantNewDeliveryStatusLog;
use Modules\RestaurantNew\Entities\RestaurantNewDeliveryZone;

class RestaurantDeliveryService
{
    public function createZone(array $data): RestaurantNewDeliveryZone
    {
        return RestaurantNewDeliveryZone::create($this->tenantData($data) + [
            'zone_name' => Arr::get($data, 'zone_name'),
            'base_delivery_charge' => Arr::get($data, 'base_delivery_charge', 0),
            'free_delivery_minimum' => Arr::get($data, 'free_delivery_minimum', 0),
            'estimated_minutes' => Arr::get($data, 'estimated_minutes', 30),
            'is_active' => Arr::get($data, 'is_active', 1),
        ]);
    }

    public function createRider(array $data): RestaurantNewDeliveryRider
    {
        return RestaurantNewDeliveryRider::create($this->tenantData($data) + [
            'rider_name' => Arr::get($data, 'rider_name'),
            'mobile' => Arr::get($data, 'mobile'),
            'vehicle_no' => Arr::get($data, 'vehicle_no'),
            'is_active' => Arr::get($data, 'is_active', 1),
            'is_available' => Arr::get($data, 'is_available', 1),
        ]);
    }

    public function saveCustomerAddress(array $data): RestaurantNewCustomerAddress
    {
        if (! empty($data['is_default'])) {
            RestaurantNewCustomerAddress::where('business_id', $data['business_id'] ?? session('business.id'))
                ->where('customer_id', Arr::get($data, 'customer_id'))
                ->update(['is_default' => 0]);
        }

        return RestaurantNewCustomerAddress::create($this->tenantData($data) + [
            'customer_id' => Arr::get($data, 'customer_id'),
            'contact_name' => Arr::get($data, 'contact_name'),
            'mobile' => Arr::get($data, 'mobile'),
            'address_line_1' => Arr::get($data, 'address_line_1'),
            'address_line_2' => Arr::get($data, 'address_line_2'),
            'city' => Arr::get($data, 'city'),
            'landmark' => Arr::get($data, 'landmark'),
            'delivery_zone_id' => Arr::get($data, 'delivery_zone_id'),
            'is_default' => Arr::get($data, 'is_default', 0),
        ]);
    }

    public function createDeliveryOrder(array $data): RestaurantNewDeliveryOrder
    {
        return DB::transaction(function () use ($data) {
            $order = RestaurantNewDeliveryOrder::create($this->tenantData($data) + [
                'restaurant_order_id' => Arr::get($data, 'restaurant_order_id'),
                'customer_id' => Arr::get($data, 'customer_id'),
                'customer_address_id' => Arr::get($data, 'customer_address_id'),
                'delivery_zone_id' => Arr::get($data, 'delivery_zone_id'),
                'delivery_rider_id' => Arr::get($data, 'delivery_rider_id'),
                'delivery_charge' => Arr::get($data, 'delivery_charge', 0),
                'cod_amount' => Arr::get($data, 'cod_amount', 0),
                'card_amount' => Arr::get($data, 'card_amount', 0),
                'delivery_status' => Arr::get($data, 'delivery_status', 'pending'),
                'payment_collection_status' => Arr::get($data, 'payment_collection_status', 'pending'),
                'special_instructions' => Arr::get($data, 'special_instructions'),
                'assigned_at' => Arr::get($data, 'delivery_rider_id') ? Carbon::now() : null,
            ]);

            $this->logStatus($order, 'pending', 'Delivery order created');

            if ($order->delivery_rider_id) {
                RestaurantNewDeliveryRider::whereKey($order->delivery_rider_id)->update([
                    'is_available' => 0,
                    'last_assigned_at' => Carbon::now(),
                ]);
            }

            return $order;
        });
    }

    public function changeStatus(RestaurantNewDeliveryOrder $order, string $status, ?string $note = null): RestaurantNewDeliveryOrder
    {
        $updates = ['delivery_status' => $status];
        if ($status === 'dispatched') {
            $updates['dispatched_at'] = Carbon::now();
        }
        if ($status === 'delivered') {
            $updates['delivered_at'] = Carbon::now();
            $updates['payment_collection_status'] = 'collected';
        }
        if ($status === 'cancelled') {
            $updates['cancelled_at'] = Carbon::now();
        }

        $order->fill($updates)->save();
        $this->logStatus($order, $status, $note);

        if (in_array($status, ['delivered', 'cancelled'], true) && $order->delivery_rider_id) {
            RestaurantNewDeliveryRider::whereKey($order->delivery_rider_id)->update(['is_available' => 1]);
        }

        return $order->fresh();
    }

    public function assignRider(RestaurantNewDeliveryOrder $order, int $riderId): RestaurantNewDeliveryOrder
    {
        $order->fill([
            'delivery_rider_id' => $riderId,
            'assigned_at' => Carbon::now(),
            'delivery_status' => 'assigned',
        ])->save();

        RestaurantNewDeliveryRider::whereKey($riderId)->update(['is_available' => 0, 'last_assigned_at' => Carbon::now()]);
        $this->logStatus($order, 'assigned', 'Rider assigned');

        return $order->fresh();
    }

    protected function logStatus(RestaurantNewDeliveryOrder $order, string $status, ?string $note = null): void
    {
        RestaurantNewDeliveryStatusLog::create([
            'business_id' => $order->business_id,
            'business_location_id' => $order->business_location_id,
            'delivery_order_id' => $order->id,
            'status' => $status,
            'note' => $note,
            'changed_by' => Auth::id(),
            'changed_at' => Carbon::now(),
        ]);
    }

    protected function tenantData(array $data): array
    {
        return [
            'business_id' => $data['business_id'] ?? session('business.id'),
            'business_location_id' => $data['business_location_id'] ?? session('business_location.id'),
            'created_by' => $data['created_by'] ?? Auth::id(),
        ];
    }
}
