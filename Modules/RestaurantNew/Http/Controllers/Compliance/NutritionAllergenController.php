<?php

namespace Modules\RestaurantNew\Http\Controllers\Compliance;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Services\Compliance\NutritionAllergenService;

class NutritionAllergenController extends Controller
{
    protected $service;
    public function __construct(NutritionAllergenService $service) { $this->service = $service; }

    public function dashboard(Request $request) { return view('restaurantnew::compliance.dashboard', $this->service->dashboard($request)); }
    public function allergens(Request $request) { return view('restaurantnew::compliance.allergens.index', ['allergens' => $this->service->allergens($request)]); }
    public function storeAllergen(Request $request) { $this->service->storeAllergen($request->all()); return redirect()->back()->with('status', __('restaurantnew::compliance.allergen_saved')); }
    public function dietaryTags(Request $request) { return view('restaurantnew::compliance.dietary_tags.index', ['tags' => $this->service->dietaryTags($request)]); }
    public function storeDietaryTag(Request $request) { $this->service->storeDietaryTag($request->all()); return redirect()->back()->with('status', __('restaurantnew::compliance.dietary_tag_saved')); }
    public function nutritionProfiles(Request $request) { return view('restaurantnew::compliance.nutrition.index', ['profiles' => $this->service->nutritionProfiles($request)]); }
    public function storeNutritionProfile(Request $request) { $this->service->storeNutritionProfile($request->all()); return redirect()->back()->with('status', __('restaurantnew::compliance.nutrition_saved')); }
    public function complianceChecks(Request $request) { return view('restaurantnew::compliance.checks.index', ['checks' => $this->service->complianceChecks($request)]); }
    public function storeComplianceCheck(Request $request) { $this->service->storeComplianceCheck($request->all()); return redirect()->back()->with('status', __('restaurantnew::compliance.check_saved')); }
    public function customerWarnings(Request $request) { return view('restaurantnew::compliance.warnings.index', ['warnings' => $this->service->customerWarnings($request)]); }
    public function reports(Request $request) { return view('restaurantnew::compliance.reports.index', $this->service->reports($request)); }
}
