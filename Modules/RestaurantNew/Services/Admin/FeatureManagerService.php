<?php

namespace Modules\RestaurantNew\Services\Admin;

use Modules\RestaurantNew\Entities\RestaurantNewFeatureSetting;
use Modules\RestaurantNew\Services\Admin\RestaurantNewAuditService;

class FeatureManagerService
{
    public const FEATURES = [
        'restaurant_pos' => 'Restaurant POS',
        'kitchen_display' => 'Kitchen Display / KOT',
        'kitchen_bill_print' => 'Kitchen Bill/KOT Print',
        'delivery_management' => 'Delivery Management',
        'inventory_recipe_costing' => 'Inventory & Recipe Costing',
        'staff_shift_controls' => 'Staff & Shift Controls',
        'qr_menu_public_ordering' => 'QR Menu / Self Ordering',
        'promotions_combos' => 'Promotions / Combos / Happy Hours',
        'procurement_production' => 'Procurement & Production',
        'reports_dashboards' => 'Reports & Dashboards',
    ];

    public function listForBusiness(int $businessId, ?int $locationId = null): array
    {
        $rows = RestaurantNewFeatureSetting::where('business_id', $businessId)
            ->where(function ($q) use ($locationId) {
                $locationId ? $q->where('location_id', $locationId) : $q->whereNull('location_id');
            })->get()->keyBy('feature_key');

        $out = [];
        foreach (self::FEATURES as $key => $label) {
            $row = $rows->get($key);
            $out[] = [
                'feature_key' => $key,
                'label' => $label,
                'is_enabled' => $row ? (bool) $row->is_enabled : true,
                'settings' => $row ? ($row->settings ?: []) : [],
            ];
        }
        return $out;
    }

    public function saveFeature(int $businessId, ?int $locationId, string $featureKey, bool $enabled, array $settings = [], ?int $userId = null): RestaurantNewFeatureSetting
    {
        $row = RestaurantNewFeatureSetting::updateOrCreate(
            ['business_id' => $businessId, 'location_id' => $locationId, 'feature_key' => $featureKey],
            ['is_enabled' => $enabled, 'settings' => $settings, 'updated_by' => $userId, 'created_by' => $userId]
        );

        app(RestaurantNewAuditService::class)->record($businessId, $locationId, $userId, 'super_admin', 'feature_saved', 'feature_setting', $row->id, [], $row->toArray());
        return $row;
    }

    public function isEnabled(int $businessId, ?int $locationId, string $featureKey): bool
    {
        $row = RestaurantNewFeatureSetting::where('business_id', $businessId)
            ->where('feature_key', $featureKey)
            ->where(function ($q) use ($locationId) { $q->where('location_id', $locationId)->orWhereNull('location_id'); })
            ->orderByRaw('location_id IS NULL ASC')
            ->first();
        return $row ? (bool) $row->is_enabled : true;
    }
}
