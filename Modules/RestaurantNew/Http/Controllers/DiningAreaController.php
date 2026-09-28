<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewDiningArea;
use Modules\RestaurantNew\Services\CoreSetupService;

class DiningAreaController extends Controller
{
    public function index(CoreSetupService $service)
    {
        return view('restaurantnew::setup.dining_areas.index', [
            'rows' => $service->scope(RestaurantNewDiningArea::query())->withCount('tables')->orderBy('sort_order')->orderBy('name')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('restaurantnew::setup.dining_areas.form', ['row' => new RestaurantNewDiningArea()]);
    }

    public function store(Request $request, CoreSetupService $service)
    {
        RestaurantNewDiningArea::create($this->validated($request, $service));
        return redirect()->route('restaurant-new.dining-areas.index')->with('status', __('restaurantnew::lang.saved_successfully'));
    }

    public function edit($id, CoreSetupService $service)
    {
        $row = $service->scope(RestaurantNewDiningArea::query())->findOrFail($id);
        return view('restaurantnew::setup.dining_areas.form', compact('row'));
    }

    public function update(Request $request, $id, CoreSetupService $service)
    {
        $row = $service->scope(RestaurantNewDiningArea::query())->findOrFail($id);
        $row->update($this->validated($request, $service));
        return redirect()->route('restaurant-new.dining-areas.index')->with('status', __('restaurantnew::lang.updated_successfully'));
    }

    public function destroy($id, CoreSetupService $service)
    {
        $service->scope(RestaurantNewDiningArea::query())->findOrFail($id)->delete();
        return back()->with('status', __('restaurantnew::lang.deleted_successfully'));
    }

    private function validated(Request $request, CoreSetupService $service): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable'],
        ]);
        $data['business_id'] = $service->businessId();
        $data['location_id'] = $service->locationId();
        $data['is_active'] = $request->boolean('is_active', true);
        return $data;
    }
}
