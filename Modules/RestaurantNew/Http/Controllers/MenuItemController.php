<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewMenuItem;
use Modules\RestaurantNew\Services\MenuManagementService;

class MenuItemController extends Controller
{
    public function index(Request $request, MenuManagementService $service)
    {
        $rows = $service->scope(RestaurantNewMenuItem::query())
            ->with(['category', 'kitchenSection'])
            ->when($request->filled('menu_category_id'), fn ($q) => $q->where('menu_category_id', $request->menu_category_id))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($sub) use ($request) {
                $sub->where('name', 'like', '%' . $request->search . '%')->orWhere('sku', 'like', '%' . $request->search . '%');
            }))
            ->orderBy('sort_order')->orderBy('name')->paginate(25);

        return view('restaurantnew::menu.items.index', [
            'rows' => $rows,
            'categories' => $service->categoriesForDropdown(),
        ]);
    }

    public function create(MenuManagementService $service)
    {
        return view('restaurantnew::menu.items.form', $this->formData(new RestaurantNewMenuItem(), $service));
    }

    public function store(Request $request, MenuManagementService $service)
    {
        $service->saveMenuItem(new RestaurantNewMenuItem(), $this->validated($request, $service), $request->input('variants', []), $request->input('modifiers', []));
        return redirect()->route('restaurant-new.menu-items.index')->with('status', __('restaurantnew::lang.saved_successfully'));
    }

    public function edit($id, MenuManagementService $service)
    {
        $row = $service->scope(RestaurantNewMenuItem::query())->with(['variants', 'modifiers'])->findOrFail($id);
        return view('restaurantnew::menu.items.form', $this->formData($row, $service));
    }

    public function update(Request $request, $id, MenuManagementService $service)
    {
        $row = $service->scope(RestaurantNewMenuItem::query())->findOrFail($id);
        $service->saveMenuItem($row, $this->validated($request, $service), $request->input('variants', []), $request->input('modifiers', []));
        return redirect()->route('restaurant-new.menu-items.index')->with('status', __('restaurantnew::lang.updated_successfully'));
    }

    public function destroy($id, MenuManagementService $service)
    {
        $service->scope(RestaurantNewMenuItem::query())->findOrFail($id)->delete();
        return back()->with('status', __('restaurantnew::lang.deleted_successfully'));
    }

    public function toggle($id, MenuManagementService $service)
    {
        $row = $service->scope(RestaurantNewMenuItem::query())->findOrFail($id);
        $row->update(['is_active' => ! $row->is_active]);
        return back()->with('status', __('restaurantnew::lang.updated_successfully'));
    }

    public function recipes($id, MenuManagementService $service)
    {
        $row = $service->scope(RestaurantNewMenuItem::query())->with('recipes')->findOrFail($id);
        return view('restaurantnew::menu.items.recipes', compact('row'));
    }

    public function saveRecipes(Request $request, $id, MenuManagementService $service)
    {
        $row = $service->scope(RestaurantNewMenuItem::query())->findOrFail($id);
        $request->validate(['recipes' => ['nullable', 'array']]);
        $service->saveRecipes($row, $request->input('recipes', []));
        return redirect()->route('restaurant-new.menu-items.index')->with('status', __('restaurantnew::lang.updated_successfully'));
    }

    private function formData(RestaurantNewMenuItem $row, MenuManagementService $service): array
    {
        return [
            'row' => $row,
            'categories' => $service->categoriesForDropdown(),
            'kitchenSections' => $service->kitchenSectionsForDropdown(),
        ];
    }

    private function validated(Request $request, MenuManagementService $service): array
    {
        $data = $request->validate([
            'menu_category_id' => ['required', 'integer'],
            'kitchen_section_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:191'],
            'sku' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0'],
            'preparation_time_minutes' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable'],
            'is_modifier_required' => ['nullable'],
            'allow_discount' => ['nullable'],
            'track_recipe_stock' => ['nullable'],
            'available_for_dine_in' => ['nullable'],
            'available_for_takeaway' => ['nullable'],
            'available_for_delivery' => ['nullable'],
        ]);
        $data['business_id'] = $service->businessId();
        $data['location_id'] = $service->locationId();
        foreach (['is_active', 'allow_discount', 'available_for_dine_in', 'available_for_takeaway', 'available_for_delivery'] as $flag) {
            $data[$flag] = $request->boolean($flag, true);
        }
        foreach (['is_modifier_required', 'track_recipe_stock'] as $flag) {
            $data[$flag] = $request->boolean($flag, false);
        }
        return $data;
    }
}
