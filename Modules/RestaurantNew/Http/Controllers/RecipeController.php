<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewRecipe;
use Modules\RestaurantNew\Entities\RestaurantNewRecipeLine;
use Modules\RestaurantNew\Services\RestaurantRecipeCostService;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $recipes = RestaurantNewRecipe::where('business_id', $businessId)->withCount('lines')->latest()->paginate(25);

        return view('restaurantnew::recipes.index', compact('recipes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'menu_item_id' => 'required|integer',
            'name' => 'required|string|max:191',
            'yield_qty' => 'required|numeric|min:0.0001',
            'yield_unit' => 'required|string|max:30',
            'preparation_notes' => 'nullable|string',
        ]);

        $data['business_id'] = $request->session()->get('user.business_id');
        $data['location_id'] = $request->input('location_id');
        $data['created_by'] = auth()->id();

        RestaurantNewRecipe::create($data);

        return back()->with('status', __('restaurantnew::lang.recipe_saved'));
    }

    public function storeLine(Request $request, RestaurantRecipeCostService $costService)
    {
        $data = $request->validate([
            'recipe_id' => 'required|integer',
            'ingredient_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.0001',
            'unit' => 'required|string|max:30',
            'is_optional' => 'nullable|boolean',
        ]);

        $data['business_id'] = $request->session()->get('user.business_id');
        $data['is_optional'] = $request->boolean('is_optional');

        RestaurantNewRecipeLine::create($data);
        $costService->recalculate((int) $data['recipe_id']);

        return back()->with('status', __('restaurantnew::lang.recipe_line_saved'));
    }
}
