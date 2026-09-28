<?php

namespace Modules\RestaurantNew\Services\Admin;

use Modules\RestaurantNew\Entities\RestaurantNewUserAccessRule;

class UserAccessService
{
    public const ACCESS_AREAS = [
        'waiter_sale_create', 'cashier_sale_create', 'kitchen_view_received_orders', 'kitchen_print_kot_bill',
        'billing_payments', 'void_refund', 'table_operations', 'inventory', 'procurement', 'reports', 'super_admin_settings'
    ];

    public function saveRule(int $businessId, ?int $locationId, int $userId, string $area, array $actions, bool $allowed, ?int $actorId = null): RestaurantNewUserAccessRule
    {
        $row = RestaurantNewUserAccessRule::updateOrCreate(
            ['business_id' => $businessId, 'location_id' => $locationId, 'user_id' => $userId, 'access_area' => $area],
            ['allowed_actions' => $actions, 'is_allowed' => $allowed, 'created_by' => $actorId, 'updated_by' => $actorId]
        );
        app(RestaurantNewAuditService::class)->record($businessId, $locationId, $actorId, 'permissions', 'user_access_saved', 'user_access_rule', $row->id, [], $row->toArray());
        return $row;
    }

    public function isAllowed(int $businessId, ?int $locationId, int $userId, string $area, ?string $action = null): bool
    {
        $row = RestaurantNewUserAccessRule::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('access_area', $area)
            ->where(function ($q) use ($locationId) { $q->where('location_id', $locationId)->orWhereNull('location_id'); })
            ->orderByRaw('location_id IS NULL ASC')
            ->first();

        if (!$row) { return false; }
        if (!$row->is_allowed) { return false; }
        if (!$action) { return true; }
        $actions = $row->allowed_actions ?: [];
        return in_array('*', $actions, true) || in_array($action, $actions, true);
    }
}
