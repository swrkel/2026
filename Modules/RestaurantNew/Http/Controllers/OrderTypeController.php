<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\RestaurantNew\Entities\RestaurantNewOrderType;
use Modules\RestaurantNew\Services\CoreSetupService;

class OrderTypeController extends Controller
{
    public function index(CoreSetupService $service)
    {
        return view('restaurantnew::setup.order_types.index', [
            'rows' => $service->scope(RestaurantNewOrderType::query())->orderBy('sort_order')->orderBy('name')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('restaurantnew::setup.order_types.form', ['row' => new RestaurantNewOrderType()]);
    }

    public function store(Request $request, CoreSetupService $service)
    {
        RestaurantNewOrderType::create($this->validated($request, $service));
        return redirect()->route('restaurant-new.order-types.index')->with('status', __('restaurantnew::lang.saved_successfully'));
    }

    public function edit($id, CoreSetupService $service)
    {
        $row = $service->scope(RestaurantNewOrderType::query())->findOrFail($id);
        return view('restaurantnew::setup.order_types.form', compact('row'));
    }

    public function update(Request $request, $id, CoreSetupService $service)
    {
        $row = $service->scope(RestaurantNewOrderType::query())->findOrFail($id);
        $row->update($this->validated($request, $service));
        return redirect()->route('restaurant-new.order-types.index')->with('status', __('restaurantnew::lang.updated_successfully'));
    }

    public function destroy($id, CoreSetupService $service)
    {
        $service->scope(RestaurantNewOrderType::query())->findOrFail($id)->delete();
        return back()->with('status', __('restaurantnew::lang.deleted_successfully'));
    }

    private function validated(Request $request, CoreSetupService $service): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:50'],
            'requires_table' => ['nullable'],
            'requires_customer' => ['nullable'],
            'allow_delivery' => ['nullable'],
            'default_service_charge_percent' => ['nullable', 'numeric', 'min:0'],
            'default_delivery_charge' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable'],
        ]);
        $data['business_id'] = $service->businessId();
        $data['location_id'] = $service->locationId();
        $data['slug'] = $data['slug'] ?: Str::slug($data['name'], '_');
        foreach (['requires_table', 'requires_customer', 'allow_delivery', 'is_active'] as $flag) {
            $data[$flag] = $request->boolean($flag, $flag === 'is_active');
        }
        return $data;
    }
}
