<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\RestaurantNew\Entities\RestaurantNewCustomerOrderLink;
use Modules\RestaurantNew\Entities\RestaurantNewDigitalReceipt;
use Modules\RestaurantNew\Entities\RestaurantNewQrMenu;
use Modules\RestaurantNew\Entities\RestaurantNewCustomerFeedback;

class RestaurantCustomerExperienceService
{
    public function createQrMenu(array $data): RestaurantNewQrMenu
    {
        $data['public_token'] = $data['public_token'] ?? Str::random(48);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['allow_self_order'] = $data['allow_self_order'] ?? false;

        return RestaurantNewQrMenu::create($data);
    }

    public function buildCustomerOrderLink(int $businessId, int $locationId, int $orderId, string $purpose = 'status'): RestaurantNewCustomerOrderLink
    {
        return RestaurantNewCustomerOrderLink::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'restaurant_order_id' => $orderId,
            'purpose' => $purpose,
            'public_token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'status' => 'active',
        ]);
    }

    public function recordDigitalReceipt(array $data): RestaurantNewDigitalReceipt
    {
        $data['receipt_token'] = $data['receipt_token'] ?? Str::random(64);
        $data['delivery_status'] = $data['delivery_status'] ?? 'pending';

        return RestaurantNewDigitalReceipt::create($data);
    }

    public function saveFeedback(array $data): RestaurantNewCustomerFeedback
    {
        return RestaurantNewCustomerFeedback::create([
            'business_id' => $data['business_id'],
            'location_id' => $data['location_id'] ?? null,
            'restaurant_order_id' => $data['restaurant_order_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_mobile' => $data['customer_mobile'] ?? null,
            'rating_food' => $data['rating_food'] ?? null,
            'rating_service' => $data['rating_service'] ?? null,
            'rating_overall' => $data['rating_overall'] ?? null,
            'comments' => $data['comments'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    public function customerOrderStatus(string $token): ?array
    {
        $link = RestaurantNewCustomerOrderLink::where('public_token', $token)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->first();

        if (!$link) {
            return null;
        }

        $link->forceFill(['last_accessed_at' => now()])->save();

        $order = DB::table('restaurant_new_orders')->where('id', $link->restaurant_order_id)->first();
        $items = DB::table('restaurant_new_order_lines')->where('order_id', $link->restaurant_order_id)->get();

        return [
            'link' => $link,
            'order' => $order,
            'items' => $items,
        ];
    }
}
