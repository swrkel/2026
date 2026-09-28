<?php

namespace Modules\DistributionNew\Services\Vehicles;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewVehicle;

class DisnewVehicleService
{
    protected DisnewVehicleLimitService $limitService;

    public function __construct(DisnewVehicleLimitService $limitService)
    {
        $this->limitService = $limitService;
    }

    public function create(array $data): DisnewVehicle
    {
        $this->limitService->assertCanCreate((int) $data['business_id']);
        return DB::transaction(function () use ($data) {
            return DisnewVehicle::create($data);
        });
    }

    public function update(DisnewVehicle $vehicle, array $data): DisnewVehicle
    {
        return DB::transaction(function () use ($vehicle, $data) {
            $vehicle->update($data);
            return $vehicle->fresh();
        });
    }
}
