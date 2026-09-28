<?php

namespace Modules\RestaurantNew\Services\Compliance;

use Illuminate\Http\Request;
use Modules\RestaurantNew\Entities\RestaurantNewAllergen;
use Modules\RestaurantNew\Entities\RestaurantNewDietaryTag;
use Modules\RestaurantNew\Entities\RestaurantNewNutritionProfile;
use Modules\RestaurantNew\Entities\RestaurantNewRecipeComplianceCheck;
use Modules\RestaurantNew\Entities\RestaurantNewCustomerAllergyWarning;

class NutritionAllergenService
{
    protected function businessId(array $data = []) { return $data['business_id'] ?? session('business.id') ?? request()->session()->get('user.business_id'); }
    protected function locationId(array $data = []) { return $data['location_id'] ?? request()->get('location_id') ?? null; }

    public function dashboard(Request $request): array
    {
        $businessId = $this->businessId();
        return [
            'allergenCount' => RestaurantNewAllergen::where('business_id', $businessId)->where('is_active', 1)->count(),
            'dietaryTagCount' => RestaurantNewDietaryTag::where('business_id', $businessId)->where('is_active', 1)->count(),
            'nutritionProfileCount' => RestaurantNewNutritionProfile::where('business_id', $businessId)->count(),
            'pendingChecks' => RestaurantNewRecipeComplianceCheck::where('business_id', $businessId)->where('status', 'pending')->count(),
            'openWarnings' => RestaurantNewCustomerAllergyWarning::where('business_id', $businessId)->where('warning_status', 'shown')->count(),
            'recentWarnings' => RestaurantNewCustomerAllergyWarning::where('business_id', $businessId)->latest()->limit(20)->get(),
        ];
    }

    public function allergens(Request $request) { return RestaurantNewAllergen::where('business_id', $this->businessId())->latest()->paginate(25); }
    public function dietaryTags(Request $request) { return RestaurantNewDietaryTag::where('business_id', $this->businessId())->latest()->paginate(25); }
    public function nutritionProfiles(Request $request) { return RestaurantNewNutritionProfile::where('business_id', $this->businessId())->latest()->paginate(25); }
    public function complianceChecks(Request $request) { return RestaurantNewRecipeComplianceCheck::where('business_id', $this->businessId())->latest()->paginate(25); }
    public function customerWarnings(Request $request) { return RestaurantNewCustomerAllergyWarning::where('business_id', $this->businessId())->latest()->paginate(25); }

    public function storeAllergen(array $data) { $data['business_id'] = $this->businessId($data); return RestaurantNewAllergen::create($data); }
    public function storeDietaryTag(array $data) { $data['business_id'] = $this->businessId($data); return RestaurantNewDietaryTag::create($data); }
    public function storeNutritionProfile(array $data) { $data['business_id'] = $this->businessId($data); $data['location_id'] = $this->locationId($data); return RestaurantNewNutritionProfile::updateOrCreate(['business_id'=>$data['business_id'], 'menu_item_id'=>$data['menu_item_id']], $data); }
    public function storeComplianceCheck(array $data) { $data['business_id'] = $this->businessId($data); $data['location_id'] = $this->locationId($data); return RestaurantNewRecipeComplianceCheck::create($data); }

    public function warningForOrderItem(array $data): ?RestaurantNewCustomerAllergyWarning
    {
        if (empty($data['allergen_id'])) { return null; }
        $data['business_id'] = $this->businessId($data);
        $data['location_id'] = $this->locationId($data);
        $data['warning_status'] = $data['warning_status'] ?? 'shown';
        return RestaurantNewCustomerAllergyWarning::create($data);
    }

    public function reports(Request $request): array
    {
        $businessId = $this->businessId();
        return [
            'allergens' => RestaurantNewAllergen::where('business_id', $businessId)->orderBy('name')->get(),
            'nutritionProfiles' => RestaurantNewNutritionProfile::where('business_id', $businessId)->latest()->limit(100)->get(),
            'warnings' => RestaurantNewCustomerAllergyWarning::where('business_id', $businessId)->latest()->limit(100)->get(),
            'checks' => RestaurantNewRecipeComplianceCheck::where('business_id', $businessId)->latest()->limit(100)->get(),
        ];
    }
}
