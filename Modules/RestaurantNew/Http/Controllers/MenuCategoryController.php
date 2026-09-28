<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewMenuCategory;
use Modules\RestaurantNew\Services\MenuManagementService;

class MenuCategoryController extends Controller
{
    public function index(MenuManagementService $service)
    {
        $rows = $service->scope(RestaurantNewMenuCategory::query())
            ->with('parent')->withCount('items')
            ->orderBy('sort_order')->orderBy('name')->paginate(25);

        return view('restaurantnew::menu.categories.index', compact('rows'));
    }

    public function create(MenuManagementService $service)
    {
        return view('restaurantnew::menu.categories.form', [
            'row' => new RestaurantNewMenuCategory(),
            'parents' => $service->categoriesForDropdown(),
        ]);
    }

    public function store(Request $request, MenuManagementService $service)
    {
        RestaurantNewMenuCategory::create($this->validated($request, $service));
        return redirect()->route('restaurant-new.menu-categories.index')->with('status', __('restaurantnew::lang.saved_successfully'));
    }

    public function edit($id, MenuManagementService $service)
    {
        $row = $service->scope(RestaurantNewMenuCategory::query())->findOrFail($id);
        return view('restaurantnew::menu.categories.form', [
            'row' => $row,
            'parents' => $service->categoriesForDropdown($row->id),
        ]);
    }

    public function update(Request $request, $id, MenuManagementService $service)
    {
        $row = $service->scope(RestaurantNewMenuCategory::query())->findOrFail($id);
        $row->update($this->validated($request, $service));
        return redirect()->route('restaurant-new.menu-categories.index')->with('status', __('restaurantnew::lang.updated_successfully'));
    }

    public function destroy($id, MenuManagementService $service)
    {
        $service->scope(RestaurantNewMenuCategory::query())->findOrFail($id)->delete();
        return back()->with('status', __('restaurantnew::lang.deleted_successfully'));
    }

    public function toggle($id, MenuManagementService $service)
    {
        $row = $service->scope(RestaurantNewMenuCategory::query())->findOrFail($id);
        $row->update(['is_active' => ! $row->is_active]);
        return back()->with('status', __('restaurantnew::lang.updated_successfully'));
    }

    private function validated(Request $request, MenuManagementService $service): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable'],
            'available_for_dine_in' => ['nullable'],
            'available_for_takeaway' => ['nullable'],
            'available_for_delivery' => ['nullable'],
        ]);
        $data['business_id'] = $service->businessId();
        $data['location_id'] = $service->locationId();
        foreach (['is_active', 'available_for_dine_in', 'available_for_takeaway', 'available_for_delivery'] as $flag) {
            $data[$flag] = $request->boolean($flag, true);
        }
        return $data;
    }
}
