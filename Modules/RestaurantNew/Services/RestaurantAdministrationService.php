<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewPromotion;
use Modules\RestaurantNew\Entities\RestaurantNewComboMeal;
use Modules\RestaurantNew\Entities\RestaurantNewHappyHour;
use Modules\RestaurantNew\Entities\RestaurantNewBuffetPackage;
use Modules\RestaurantNew\Entities\RestaurantNewBanquetEvent;
use Modules\RestaurantNew\Entities\RestaurantNewCateringOrder;

class RestaurantAdministrationService
{
    public function createPromotion(array $data): RestaurantNewPromotion
    {
        return DB::transaction(function () use ($data) {
            $promotion = RestaurantNewPromotion::create($data);
            foreach (($data['rules'] ?? []) as $rule) {
                $promotion->rules()->create($rule);
            }
            return $promotion;
        });
    }

    public function createComboMeal(array $data): RestaurantNewComboMeal
    {
        return DB::transaction(function () use ($data) {
            $combo = RestaurantNewComboMeal::create($data);
            foreach (($data['items'] ?? []) as $item) {
                $combo->items()->create($item);
            }
            return $combo;
        });
    }

    public function activePromotions(int $businessId, ?int $locationId = null)
    {
        return RestaurantNewPromotion::query()
            ->where('business_id', $businessId)
            ->when($locationId, fn ($q) => $q->where(function ($qq) use ($locationId) {
                $qq->whereNull('location_id')->orWhere('location_id', $locationId);
            }))
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->orderBy('priority')
            ->get();
    }

    public function storeHappyHour(array $data): RestaurantNewHappyHour
    {
        return RestaurantNewHappyHour::create($data);
    }

    public function storeBuffetPackage(array $data): RestaurantNewBuffetPackage
    {
        return RestaurantNewBuffetPackage::create($data);
    }

    public function storeBanquetEvent(array $data): RestaurantNewBanquetEvent
    {
        return RestaurantNewBanquetEvent::create($data);
    }

    public function storeCateringOrder(array $data): RestaurantNewCateringOrder
    {
        return RestaurantNewCateringOrder::create($data);
    }
}
