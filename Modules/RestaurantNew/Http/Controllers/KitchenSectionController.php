<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenSection;
use Modules\RestaurantNew\Services\CoreSetupService;

class KitchenSectionController extends Controller
{
    public function index(CoreSetupService $service)
    {
        return view('restaurantnew::setup.kitchen_sections.index', [
            'rows' => $service->scope(RestaurantNewKitchenSection::query())->orderBy('sort_order')->orderBy('name')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('restaurantnew::setup.kitchen_sections.form', ['row' => new RestaurantNewKitchenSection()]);
    }

    public function store(Request $request, CoreSetupService $service)
    {
        RestaurantNewKitchenSection::create($this->validated($request, $service));
        return redirect()->route('restaurant-new.kitchen-sections.index')->with('status', __('restaurantnew::lang.saved_successfully'));
    }

    public function edit($id, CoreSetupService $service)
    {
        $row = $service->scope(RestaurantNewKitchenSection::query())->findOrFail($id);
        return view('restaurantnew::setup.kitchen_sections.form', compact('row'));
    }

    public function update(Request $request, $id, CoreSetupService $service)
    {
        $row = $service->scope(RestaurantNewKitchenSection::query())->findOrFail($id);
        $row->update($this->validated($request, $service));
        return redirect()->route('restaurant-new.kitchen-sections.index')->with('status', __('restaurantnew::lang.updated_successfully'));
    }

    public function destroy($id, CoreSetupService $service)
    {
        $service->scope(RestaurantNewKitchenSection::query())->findOrFail($id)->delete();
        return back()->with('status', __('restaurantnew::lang.deleted_successfully'));
    }

    private function validated(Request $request, CoreSetupService $service): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'print_kot' => ['nullable'],
            'show_on_kds' => ['nullable'],
            'is_active' => ['nullable'],
        ]);
        $data['business_id'] = $service->businessId();
        $data['location_id'] = $service->locationId();
        $data['print_kot'] = $request->boolean('print_kot', true);
        $data['show_on_kds'] = $request->boolean('show_on_kds', true);
        $data['is_active'] = $request->boolean('is_active', true);
        return $data;
    }
}
