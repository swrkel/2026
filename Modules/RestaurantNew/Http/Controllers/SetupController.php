<?php

namespace Modules\RestaurantNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\DiningTable;
use Modules\RestaurantNew\Entities\Floor;
use Modules\RestaurantNew\Entities\KitchenStation;
use Modules\RestaurantNew\Entities\Printer;
use Modules\RestaurantNew\Services\TenantScopeService;

class SetupController extends Controller
{
    public function index(TenantScopeService $scope)
    {
        $floorsQuery = Floor::with('tables');
        $stationsQuery = KitchenStation::query();
        $printersQuery = Printer::query();

        return view('restaurantnew::setup.index', [
            'floors' => $scope->applyOptionalLocationScope($floorsQuery)->orderBy('sort_order')->get(),
            'stations' => $scope->applyOptionalLocationScope($stationsQuery)->orderBy('sort_order')->get(),
            'printers' => $scope->applyOptionalLocationScope($printersQuery)->orderBy('name')->get(),
            'locations' => $scope->locationOptions(),
        ]);
    }

    public function floor(Request $request, TenantScopeService $scope)
    {
        $data = $request->validate(['location_id' => 'nullable|integer', 'name' => 'required|string|max:120', 'sort_order' => 'nullable|integer|min:0']);
        $locationId = (int) ($data['location_id'] ?? 0) ?: null;
        $scope->assertLocationAccess($locationId);
        Floor::create(['business_id' => $scope->businessId(), 'location_id' => $locationId, 'name' => $data['name'], 'sort_order' => $data['sort_order'] ?? 0, 'is_active' => true]);

        return back()->with('success', 'Floor added.');
    }

    public function table(Request $request, TenantScopeService $scope)
    {
        $data = $request->validate(['location_id' => 'nullable|integer', 'floor_id' => 'nullable|integer', 'table_code' => 'required|string|max:50', 'name' => 'required|string|max:120', 'capacity' => 'required|integer|min:1|max:100']);
        $locationId = (int) ($data['location_id'] ?? 0) ?: null;
        $scope->assertLocationAccess($locationId);
        if (! empty($data['floor_id'])) {
            $floor = Floor::findOrFail((int) $data['floor_id']);
            $scope->assertBusinessRecord($floor, $scope->businessId());
            if ($floor->location_id && $locationId && (int) $floor->location_id !== $locationId) {
                throw ValidationException::withMessages(['floor_id' => 'The selected floor belongs to another location.']);
            }
            $locationId ??= $floor->location_id;
        }
        DiningTable::create(['business_id' => $scope->businessId(), 'location_id' => $locationId, 'floor_id' => (int) ($data['floor_id'] ?? 0) ?: null, 'table_code' => $data['table_code'], 'name' => $data['name'], 'capacity' => $data['capacity'], 'is_active' => true]);

        return back()->with('success', 'Dining table added.');
    }

    public function station(Request $request, TenantScopeService $scope)
    {
        $data = $request->validate(['location_id' => 'nullable|integer', 'station_code' => 'required|string|max:50', 'name' => 'required|string|max:120', 'screen_colour' => 'nullable|string|max:30', 'sort_order' => 'nullable|integer|min:0']);
        $locationId = (int) ($data['location_id'] ?? 0) ?: null;
        $scope->assertLocationAccess($locationId);
        KitchenStation::create(['business_id' => $scope->businessId(), 'location_id' => $locationId, 'station_code' => $data['station_code'], 'name' => $data['name'], 'screen_colour' => $data['screen_colour'] ?? 'blue', 'sort_order' => $data['sort_order'] ?? 0, 'is_active' => true]);

        return back()->with('success', 'Kitchen station added.');
    }

    public function printer(Request $request, TenantScopeService $scope)
    {
        $data = $request->validate(['location_id' => 'nullable|integer', 'station_id' => 'nullable|integer', 'name' => 'required|string|max:120', 'printer_type' => 'required|in:browser,network,bluetooth,usb', 'paper_size' => 'required|in:58mm,80mm,A4', 'connection_type' => 'required|string|max:30', 'connection_value' => 'nullable|string|max:255']);
        $locationId = (int) ($data['location_id'] ?? 0) ?: null;
        $scope->assertLocationAccess($locationId);
        if (! empty($data['station_id'])) {
            $station = KitchenStation::findOrFail((int) $data['station_id']);
            $scope->assertBusinessRecord($station, $scope->businessId());
            if ($station->location_id && $locationId && (int) $station->location_id !== $locationId) {
                throw ValidationException::withMessages(['station_id' => 'The selected kitchen station belongs to another location.']);
            }
            $locationId ??= $station->location_id;
        }
        Printer::create(['business_id' => $scope->businessId(), 'location_id' => $locationId, 'station_id' => (int) ($data['station_id'] ?? 0) ?: null, 'name' => $data['name'], 'printer_type' => $data['printer_type'], 'paper_size' => $data['paper_size'], 'connection_type' => $data['connection_type'], 'connection_value' => $data['connection_value'] ?? null, 'is_active' => true]);

        return back()->with('success', 'Printer added.');
    }
}
