<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewDiningArea;
use Modules\RestaurantNew\Entities\RestaurantNewTable;
use Modules\RestaurantNew\Services\CoreSetupService;

class TableController extends Controller
{
    public function index(CoreSetupService $service)
    {
        return view('restaurantnew::setup.tables.index', [
            'rows' => $service->scope(RestaurantNewTable::query())->with('diningArea')->orderBy('sort_order')->orderBy('name')->paginate(25),
        ]);
    }

    public function create(CoreSetupService $service)
    {
        return view('restaurantnew::setup.tables.form', [
            'row' => new RestaurantNewTable(),
            'diningAreas' => $service->scope(RestaurantNewDiningArea::query())->where('is_active', 1)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request, CoreSetupService $service)
    {
        RestaurantNewTable::create($this->validated($request, $service));
        return redirect()->route('restaurant-new.tables.index')->with('status', __('restaurantnew::lang.saved_successfully'));
    }

    public function edit($id, CoreSetupService $service)
    {
        $row = $service->scope(RestaurantNewTable::query())->findOrFail($id);
        $diningAreas = $service->scope(RestaurantNewDiningArea::query())->where('is_active', 1)->orderBy('name')->pluck('name', 'id');
        return view('restaurantnew::setup.tables.form', compact('row', 'diningAreas'));
    }

    public function update(Request $request, $id, CoreSetupService $service)
    {
        $row = $service->scope(RestaurantNewTable::query())->findOrFail($id);
        $row->update($this->validated($request, $service));
        return redirect()->route('restaurant-new.tables.index')->with('status', __('restaurantnew::lang.updated_successfully'));
    }

    public function destroy($id, CoreSetupService $service)
    {
        $service->scope(RestaurantNewTable::query())->findOrFail($id)->delete();
        return back()->with('status', __('restaurantnew::lang.deleted_successfully'));
    }

    private function validated(Request $request, CoreSetupService $service): array
    {
        $data = $request->validate([
            'dining_area_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:191'],
            'table_code' => ['nullable', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);
        $data['business_id'] = $service->businessId();
        $data['location_id'] = $service->locationId();
        $data['capacity'] = $data['capacity'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', true);
        return $data;
    }
}
