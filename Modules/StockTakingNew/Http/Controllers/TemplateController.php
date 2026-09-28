<?php

namespace Modules\StockTakingNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Entities\StockTakeTemplate;
use Modules\StockTakingNew\Services\MasterDataBridgeService;
use Modules\StockTakingNew\Services\TenantScopeService;

class TemplateController extends Controller
{
    public function index(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters)
    {
        $businessId = $scope->businessId($request);
        abort_unless($businessId, 403);
        $templates = StockTakeTemplate::where('business_id', $businessId)->withCount('lines')->latest()->paginate(30);
        $categories = $masters->categories($businessId);
        $brands = $masters->brands($businessId);
        return view('stocktakingnew::templates.index', compact('templates', 'categories', 'brands'));
    }

    public function store(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters)
    {
        $businessId = $scope->businessId($request);
        abort_unless($businessId, 403);
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'count_method' => 'required|in:full,cycle,spot',
            'notes' => 'nullable|string|max:2000',
            'category_id' => 'nullable|integer|min:1',
            'brand_id' => 'nullable|integer|min:1',
        ]);
        if (! empty($data['category_id'])) {
            abort_unless($masters->categoryIsValid($businessId, (int) $data['category_id']), 422, 'Invalid category.');
        }
        if (! empty($data['brand_id'])) {
            abort_unless($masters->brandIsValid($businessId, (int) $data['brand_id']), 422, 'Invalid brand.');
        }
        StockTakeTemplate::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'count_method' => $data['count_method'],
            'scope_json' => array_filter([
                'category_id' => $data['category_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
            ]),
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);
        return back()->with('status', 'Count template created.');
    }

    public function destroy(StockTakeTemplate $template, Request $request, TenantScopeService $scope)
    {
        $scope->assertBusinessRecord($template, $scope->businessId($request));
        $template->delete();
        return back()->with('status', 'Template deleted.');
    }
}
