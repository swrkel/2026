<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewIngredient;
use Modules\RestaurantNew\Entities\RestaurantNewIngredientCategory;
use Modules\RestaurantNew\Entities\RestaurantNewIngredientStock;
use Modules\RestaurantNew\Entities\RestaurantNewStockMovement;
use Modules\RestaurantNew\Entities\RestaurantNewWastage;
use Modules\RestaurantNew\Services\RestaurantInventoryService;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $ingredients = RestaurantNewIngredient::where('business_id', $businessId)->latest()->paginate(25);

        return view('restaurantnew::inventory.index', compact('ingredients'));
    }

    public function storeIngredient(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'sku' => 'nullable|string|max:80',
            'unit' => 'required|string|max:30',
            'category_id' => 'nullable|integer',
            'purchase_price' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
        ]);

        $data['business_id'] = $request->session()->get('user.business_id');
        $data['location_id'] = $request->input('location_id');
        $data['created_by'] = auth()->id();

        RestaurantNewIngredient::create($data);

        return back()->with('status', __('restaurantnew::lang.ingredient_saved'));
    }

    public function receive(Request $request, RestaurantInventoryService $inventoryService)
    {
        $data = $request->validate([
            'location_id' => 'required|integer',
            'ingredient_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:191',
        ]);

        $inventoryService->receiveIngredient(
            (int) $request->session()->get('user.business_id'),
            (int) $data['location_id'],
            (int) $data['ingredient_id'],
            (float) $data['quantity'],
            (float) $data['unit_cost'],
            $data['notes'] ?? null
        );

        return back()->with('status', __('restaurantnew::lang.stock_received'));
    }

    public function stocks(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $stocks = RestaurantNewIngredientStock::with('ingredient')
            ->where('business_id', $businessId)
            ->latest()
            ->paginate(25);

        return view('restaurantnew::inventory.stocks', compact('stocks'));
    }

    public function movements(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $movements = RestaurantNewStockMovement::where('business_id', $businessId)->latest()->paginate(50);

        return view('restaurantnew::inventory.movements', compact('movements'));
    }

    public function wastage(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $wastages = RestaurantNewWastage::where('business_id', $businessId)->latest()->paginate(25);

        return view('restaurantnew::inventory.wastage', compact('wastages'));
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:191', 'code' => 'nullable|string|max:50']);
        $data['business_id'] = $request->session()->get('user.business_id');
        $data['location_id'] = $request->input('location_id');
        $data['created_by'] = auth()->id();
        RestaurantNewIngredientCategory::create($data);

        return back()->with('status', __('restaurantnew::lang.category_saved'));
    }
}
