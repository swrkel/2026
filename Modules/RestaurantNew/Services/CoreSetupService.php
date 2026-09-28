<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewDiningArea;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenSection;
use Modules\RestaurantNew\Entities\RestaurantNewNumberingSequence;
use Modules\RestaurantNew\Entities\RestaurantNewOrderType;
use Modules\RestaurantNew\Entities\RestaurantNewSetting;
use Modules\RestaurantNew\Entities\RestaurantNewTable;

class CoreSetupService
{
    public function businessId(): int
    {
        return (int) session('business.id');
    }

    public function locationId(): ?int
    {
        return session('business_location_id') ? (int) session('business_location_id') : null;
    }

    public function scope($query)
    {
        return $query->where('business_id', $this->businessId())
            ->when($this->locationId(), function ($q, $locationId) {
                $q->where(function ($sub) use ($locationId) {
                    $sub->whereNull('location_id')->orWhere('location_id', $locationId);
                });
            });
    }

    public function dashboardCounts(): array
    {
        return [
            'dining_areas' => $this->scope(RestaurantNewDiningArea::query())->count(),
            'tables' => $this->scope(RestaurantNewTable::query())->count(),
            'kitchen_sections' => $this->scope(RestaurantNewKitchenSection::query())->count(),
            'order_types' => $this->scope(RestaurantNewOrderType::query())->count(),
        ];
    }

    public function settings(): array
    {
        return $this->scope(RestaurantNewSetting::query())->pluck('value', 'key')->toArray();
    }

    public function saveSettings(array $data): void
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                RestaurantNewSetting::updateOrCreate([
                    'business_id' => $this->businessId(),
                    'location_id' => $this->locationId(),
                    'key' => $key,
                ], ['value' => is_bool($value) ? (int) $value : $value]);
            }
        });
    }

    public function ensureDefaultOrderTypes(): void
    {
        $defaults = [
            ['name' => 'Dine In', 'slug' => 'dine_in', 'requires_table' => true, 'sort_order' => 1],
            ['name' => 'Takeaway', 'slug' => 'takeaway', 'requires_table' => false, 'sort_order' => 2],
            ['name' => 'Delivery', 'slug' => 'delivery', 'requires_table' => false, 'requires_customer' => true, 'allow_delivery' => true, 'sort_order' => 3],
        ];

        foreach ($defaults as $row) {
            RestaurantNewOrderType::firstOrCreate([
                'business_id' => $this->businessId(),
                'location_id' => $this->locationId(),
                'slug' => $row['slug'],
            ], array_merge(['requires_customer' => false, 'allow_delivery' => false, 'is_active' => true], $row));
        }
    }

    public function ensureDefaultNumbering(): void
    {
        foreach (['order' => 'ORD-', 'kot' => 'KOT-', 'bill' => 'BILL-'] as $type => $prefix) {
            RestaurantNewNumberingSequence::firstOrCreate([
                'business_id' => $this->businessId(),
                'location_id' => $this->locationId(),
                'document_type' => $type,
            ], ['prefix' => $prefix, 'next_number' => 1, 'padding' => 5]);
        }
    }
}
