<?php

namespace Modules\BeautySaloons\Services;

use Illuminate\Support\Facades\DB;
use Modules\BeautySaloons\Entities\BeautyService;
use Modules\BeautySaloons\Entities\BeautyServicePrice;

class ServiceCatalogueService
{
    public function create(array $data): BeautyService
    {
        return DB::transaction(function () use ($data) {
            $service = BeautyService::create($this->servicePayload($data));
            BeautyServicePrice::create($this->pricePayload($service->id, $data));
            return $service;
        });
    }

    public function update(int $id, array $data): BeautyService
    {
        return DB::transaction(function () use ($id, $data) {
            $service = BeautyService::findOrFail($id);
            $service->update($this->servicePayload($data));
            BeautyServicePrice::updateOrCreate(['service_id' => $service->id], $this->pricePayload($service->id, $data));
            return $service;
        });
    }

    protected function servicePayload(array $data): array
    {
        return [
            'category_id' => $data['category_id'] ?? null,
            'service_code' => $data['service_code'] ?? null,
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? 0,
            'buffer_minutes' => $data['buffer_minutes'] ?? 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];
    }

    protected function pricePayload(int $serviceId, array $data): array
    {
        return [
            'service_id' => $serviceId,
            'business_location_id' => $data['business_location_id'] ?? null,
            'price' => $data['price'] ?? 0,
            'discount_allowed' => !empty($data['discount_allowed']) ? 1 : 0,
            'commission_applicable' => !empty($data['commission_applicable']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];
    }
}
