<?php

namespace Modules\RestaurantNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\MenuItem;
use Modules\RestaurantNew\Entities\MenuItemModifierGroup;
use Modules\RestaurantNew\Entities\Modifier;
use Modules\RestaurantNew\Entities\ModifierGroup;
use Modules\RestaurantNew\Services\TenantScopeService;

class ModifierController extends Controller
{
    public function index(TenantScopeService $scope)
    {
        return view('restaurantnew::menu.modifiers', [
            'groups' => ModifierGroup::with('modifiers')->orderBy('name')->get(),
            'items' => $scope->applyOptionalLocationScope(
                MenuItem::with('modifierGroups')->where('is_active', true)
            )->orderBy('name')->get(),
        ]);
    }

    public function storeGroup(Request $request, TenantScopeService $scope)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'is_required' => 'nullable|boolean',
            'min_select' => 'nullable|integer|min:0|max:50',
            'max_select' => 'required|integer|min:1|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $min = (int) ($data['min_select'] ?? 0);
        $max = (int) $data['max_select'];
        if ($min > $max) {
            throw ValidationException::withMessages(['min_select' => 'Minimum selections cannot exceed maximum selections.']);
        }

        ModifierGroup::create([
            'business_id' => $scope->businessId(),
            'name' => $data['name'],
            'is_required' => $request->boolean('is_required'),
            'min_select' => $min,
            'max_select' => $max,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Modifier group added.');
    }

    public function storeModifier(Request $request, TenantScopeService $scope)
    {
        $data = $request->validate([
            'modifier_group_id' => 'required|integer',
            'name' => 'required|string|max:120',
            'price_delta' => 'nullable|numeric|min:-999999999|max:999999999',
            'is_active' => 'nullable|boolean',
        ]);

        $group = ModifierGroup::findOrFail((int) $data['modifier_group_id']);
        $scope->assertBusinessRecord($group, $scope->businessId());

        Modifier::create([
            'business_id' => $scope->businessId(),
            'modifier_group_id' => $group->id,
            'name' => $data['name'],
            'price_delta' => $data['price_delta'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Modifier option added.');
    }

    public function attach(Request $request, MenuItem $item, TenantScopeService $scope)
    {
        $data = $request->validate([
            'modifier_group_id' => 'required|integer',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $scope->assertBusinessRecord($item, $scope->businessId());
        $group = ModifierGroup::findOrFail((int) $data['modifier_group_id']);
        $scope->assertBusinessRecord($group, $scope->businessId());

        MenuItemModifierGroup::withoutGlobalScopes()->updateOrCreate(
            [
                'menu_item_id' => $item->id,
                'modifier_group_id' => $group->id,
            ],
            [
                'business_id' => $scope->businessId(),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]
        );

        return back()->with('success', 'Modifier group attached to menu item.');
    }

    public function detach(MenuItem $item, ModifierGroup $group, TenantScopeService $scope)
    {
        $scope->assertBusinessRecord($item, $scope->businessId());
        $scope->assertBusinessRecord($group, $scope->businessId());

        MenuItemModifierGroup::withoutGlobalScopes()
            ->where('business_id', $scope->businessId())
            ->where('menu_item_id', $item->id)
            ->where('modifier_group_id', $group->id)
            ->delete();

        return back()->with('success', 'Modifier group detached.');
    }
}
