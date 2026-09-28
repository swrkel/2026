<?php

namespace Modules\RestaurantNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\Category;
use Modules\RestaurantNew\Entities\KitchenStation;
use Modules\RestaurantNew\Entities\MenuItem;
use Modules\RestaurantNew\Http\Requests\StoreMenuItemRequest;
use Modules\RestaurantNew\Services\TenantScopeService;

class MenuController extends Controller
{
    public function index(TenantScopeService $scope)
    {
        $categoriesQuery = Category::query();
        $itemsQuery = MenuItem::with(['category', 'station']);
        $stationsQuery = KitchenStation::where('is_active', true);

        return view('restaurantnew::menu.index', [
            'categories' => $scope->applyOptionalLocationScope($categoriesQuery)->orderBy('sort_order')->get(),
            'items' => $scope->applyOptionalLocationScope($itemsQuery)->orderBy('name')->paginate(50),
            'stations' => $scope->applyOptionalLocationScope($stationsQuery)->orderBy('name')->get(),
            'locations' => $scope->locationOptions(),
        ]);
    }

    public function storeCategory(Request $request, TenantScopeService $scope)
    {
        $data = $request->validate([
            'location_id' => 'nullable|integer',
            'name' => 'required|string|max:120',
            'colour' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $locationId = (int) ($data['location_id'] ?? 0) ?: null;
        $scope->assertLocationAccess($locationId);

        Category::create([
            'business_id' => $scope->businessId(),
            'location_id' => $locationId,
            'name' => $data['name'],
            'colour' => $data['colour'] ?? 'blue',
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Menu category added.');
    }

    public function storeItem(StoreMenuItemRequest $request, TenantScopeService $scope)
    {
        $data = $this->normaliseItemData($request, $scope);
        $data['business_id'] = $scope->businessId();
        MenuItem::create($data);

        return back()->with('success', 'Menu item added.');
    }

    public function updateItem(StoreMenuItemRequest $request, MenuItem $item, TenantScopeService $scope)
    {
        $scope->assertBusinessRecord($item, $scope->businessId());
        $item->update($this->normaliseItemData($request, $scope, false));

        return back()->with('success', 'Menu item updated.');
    }

    public function availability(MenuItem $item, TenantScopeService $scope)
    {
        $scope->assertBusinessRecord($item, $scope->businessId());
        $item->update(['is_available' => ! $item->is_available]);

        return back()->with('success', 'Menu availability changed.');
    }

    private function normaliseItemData(StoreMenuItemRequest $request, TenantScopeService $scope, bool $defaultTrue = true): array
    {
        $data = $request->validated();
        $locationId = (int) ($data['location_id'] ?? 0) ?: null;
        $scope->assertLocationAccess($locationId);
        $data['location_id'] = $locationId;

        if (! empty($data['category_id'])) {
            $category = Category::findOrFail((int) $data['category_id']);
            $scope->assertBusinessRecord($category, $scope->businessId());
            $locationId = $this->mergeRelatedLocation($locationId, $category->location_id, 'category_id');
        }
        if (! empty($data['station_id'])) {
            $station = KitchenStation::findOrFail((int) $data['station_id']);
            $scope->assertBusinessRecord($station, $scope->businessId());
            $locationId = $this->mergeRelatedLocation($locationId, $station->location_id, 'station_id');
        }
        $data['location_id'] = $locationId;

        foreach (['is_dine_in', 'is_takeaway', 'is_delivery', 'is_available', 'is_active'] as $key) {
            $data[$key] = $request->boolean($key, $defaultTrue);
        }

        return $data;
    }

    private function mergeRelatedLocation(?int $selectedLocationId, $relatedLocationId, string $field): ?int
    {
        $relatedLocationId = (int) $relatedLocationId ?: null;
        if ($selectedLocationId && $relatedLocationId && $selectedLocationId !== $relatedLocationId) {
            throw ValidationException::withMessages([$field => 'The selected record belongs to another location.']);
        }

        return $selectedLocationId ?: $relatedLocationId;
    }
}
