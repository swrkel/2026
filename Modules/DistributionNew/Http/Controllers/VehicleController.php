<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewVehicle;
use Modules\DistributionNew\Services\Vehicles\DisnewVehicleLimitService;
use Modules\DistributionNew\Services\Vehicles\DisnewVehicleService;

class VehicleController extends Controller
{
    public function index(Request $request, DisnewVehicleLimitService $limitService)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $vehicles = DisnewVehicle::forBusiness($businessId)
            ->forLocation($request->get('business_location_id'))
            ->orderBy('vehicle_no')
            ->paginate(25);

        $limitState = $limitService->canCreate($businessId);
        return view('distributionnew::vehicles.index', compact('vehicles', 'limitState'));
    }

    public function create(Request $request, DisnewVehicleLimitService $limitService)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $limitState = $limitService->canCreate($businessId);
        return view('distributionnew::vehicles.create', compact('limitState'));
    }

    public function store(Request $request, DisnewVehicleService $service)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $data = $this->validatedData($request) + [
            'business_id' => $businessId,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ];
        $service->create($data);
        return redirect()->route('distributionnew.vehicles.index')->with('status', __('distributionnew::lang.vehicle_created'));
    }

    public function edit(DisnewVehicle $vehicle)
    {
        return view('distributionnew::vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, DisnewVehicle $vehicle, DisnewVehicleService $service)
    {
        $data = $this->validatedData($request) + ['updated_by' => auth()->id()];
        $service->update($vehicle, $data);
        return redirect()->route('distributionnew.vehicles.index')->with('status', __('distributionnew::lang.vehicle_updated'));
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'business_location_id' => ['nullable', 'integer'],
            'vehicle_no' => ['required', 'string', 'max:50'],
            'vehicle_name' => ['nullable', 'string', 'max:100'],
            'vehicle_type' => ['nullable', 'string', 'max:50'],
            'capacity_qty' => ['nullable', 'numeric'],
            'capacity_volume' => ['nullable', 'numeric'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'driver_mobile' => ['nullable', 'string', 'max:30'],
            'helper_name' => ['nullable', 'string', 'max:100'],
            'helper_mobile' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:active,maintenance,inactive'],
            'note' => ['nullable', 'string'],
        ]);
    }
}
