<?php

namespace Modules\StockTakingNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Entities\StockTakeAssignment;
use Modules\StockTakingNew\Entities\StockTakeSession;
use Modules\StockTakingNew\Entities\StockTakeTemplate;
use Modules\StockTakingNew\Http\Requests\StoreSessionRequest;
use Modules\StockTakingNew\Services\MasterDataBridgeService;
use Modules\StockTakingNew\Services\SessionService;
use Modules\StockTakingNew\Services\SettingsService;
use Modules\StockTakingNew\Services\TenantScopeService;

class SessionController extends Controller
{
    public function index(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters)
    {
        $businessId = $scope->businessId($request);
        abort_unless($businessId, 403);

        $query = StockTakeSession::where('business_id', $businessId);
        $scope->applyLocationScope($query);
        $sessions = $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->location_id))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('count_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('count_date', '<=', $request->date_to))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%' . $request->search . '%';
                $q->where(fn ($nested) => $nested->where('stock_take_no', 'like', $term)->orWhere('title', 'like', $term));
            })
            ->latest('count_date')->latest('id')->paginate(30)->withQueryString();

        $locations = $masters->locations($businessId);
        $stores = $masters->stores($businessId, $request->integer('location_id') ?: null);
        return view('stocktakingnew::sessions.index', compact('sessions', 'locations', 'stores'));
    }

    public function create(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters, SettingsService $settings)
    {
        $businessId = $scope->businessId($request);
        abort_unless($businessId, 403);

        $locations = $masters->locations($businessId);
        $stores = [];
        $users = $masters->users($businessId);
        $categories = $masters->categories($businessId);
        $brands = $masters->brands($businessId);
        $templates = StockTakeTemplate::where('business_id', $businessId)->where('is_active', 1)
            ->orderBy('name')->pluck('name', 'id');
        $moduleSettings = $settings->all($businessId);

        return view('stocktakingnew::sessions.create', compact(
            'locations', 'stores', 'users', 'categories', 'brands', 'templates', 'moduleSettings'
        ));
    }

    public function store(
        StoreSessionRequest $request,
        TenantScopeService $scope,
        MasterDataBridgeService $masters,
        SessionService $service
    ) {
        $businessId = $scope->businessId($request);
        abort_unless($businessId, 403);
        $data = $request->validated();

        abort_unless($masters->locationIsValid($businessId, (int) $data['location_id']), 422, 'Invalid or unauthorized location.');
        if (! empty($data['store_id'])) {
            abort_unless($masters->storeIsValid($businessId, (int) $data['location_id'], (int) $data['store_id']), 422, 'Invalid store for the selected location.');
        }
        if (! empty($data['template_id'])) {
            abort_unless(StockTakeTemplate::where('business_id', $businessId)->whereKey($data['template_id'])->exists(), 422, 'Invalid template.');
        }

        $assignedUsers = array_values(array_unique(array_map('intval', $data['assigned_users'] ?? [])));
        $validBusinessUsers = array_map('intval', array_keys($masters->users($businessId)));
        abort_if(array_diff($assignedUsers, $validBusinessUsers) !== [], 422, 'One or more assigned users do not belong to this business.');

        $data['freeze_stock'] = $request->boolean('freeze_stock');
        $data['require_recount'] = $request->boolean('require_recount');
        unset($data['assigned_users']);
        $session = $service->create($businessId, auth()->id(), $data);

        foreach ($assignedUsers as $userId) {
            StockTakeAssignment::firstOrCreate([
                'business_id' => $businessId,
                'session_id' => $session->id,
                'user_id' => (int) $userId,
                'role' => 'counter',
            ], [
                'status' => 'assigned', 'assigned_at' => now(), 'assigned_by' => auth()->id(),
            ]);
        }

        return redirect()->route('stock-taking-new.sessions.show', $session)
            ->with('status', 'Stock taking session created. Prepare the snapshot to load products.');
    }

    public function show(StockTakeSession $session, Request $request, TenantScopeService $scope, MasterDataBridgeService $masters)
    {
        $scope->assertBusinessRecord($session, $scope->businessId($request));
        $lines = $session->lines()->orderBy('product_name')->orderBy('sku')->paginate(50)->withQueryString();
        $session->load(['assignments', 'approvals']);
        $locationName = $masters->locationName($session->location_id);
        $storeName = $masters->storeName($session->store_id);
        $activeBlindCount = $session->count_mode === 'blind'
            && in_array($session->status, ['prepared', 'counting', 'recount'], true);
        $canViewSystemQuantity = ! $activeBlindCount
            || (auth()->user()?->can('stock_taking_new.approvals.view') ?? false)
            || (auth()->user()?->can('stock_taking_new.reports.view') ?? false);

        return view('stocktakingnew::sessions.show', compact(
            'session', 'lines', 'locationName', 'storeName', 'canViewSystemQuantity'
        ));
    }

    public function edit(StockTakeSession $session, Request $request, TenantScopeService $scope, MasterDataBridgeService $masters)
    {
        $businessId = $scope->businessId($request);
        $scope->assertBusinessRecord($session, $businessId);
        abort_unless($session->status === 'draft', 409);

        $locations = $masters->locations($businessId);
        $stores = $masters->stores($businessId, $session->location_id);
        return view('stocktakingnew::sessions.edit', compact('session', 'locations', 'stores'));
    }

    public function update(
        StockTakeSession $session,
        Request $request,
        TenantScopeService $scope,
        MasterDataBridgeService $masters
    ) {
        $businessId = $scope->businessId($request);
        $scope->assertBusinessRecord($session, $businessId);
        abort_unless($session->status === 'draft', 409);

        $data = $request->validate([
            'title' => 'required|string|max:160',
            'count_date' => 'required|date',
            'location_id' => 'required|integer',
            'store_id' => 'nullable|integer',
            'count_method' => 'required|in:full,cycle,spot',
            'count_mode' => 'required|in:blind,open',
            'notes' => 'nullable|string|max:3000',
        ]);
        abort_unless($masters->locationIsValid($businessId, (int) $data['location_id']), 422, 'Invalid or unauthorized location.');
        if (! empty($data['store_id'])) {
            abort_unless($masters->storeIsValid($businessId, (int) $data['location_id'], (int) $data['store_id']), 422, 'Invalid store.');
        }

        $session->update($data);
        return redirect()->route('stock-taking-new.sessions.show', $session)->with('status', 'Session updated.');
    }

    public function prepare(StockTakeSession $session, Request $request, TenantScopeService $scope, SessionService $service)
    {
        $scope->assertBusinessRecord($session, $scope->businessId($request));
        try {
            $count = $service->prepare($session);
            return back()->with('status', "Snapshot prepared with {$count} product lines.");
        } catch (\Throwable $exception) {
            return back()->withErrors($exception->getMessage());
        }
    }

    public function start(StockTakeSession $session, Request $request, TenantScopeService $scope, SessionService $service)
    {
        $scope->assertBusinessRecord($session, $scope->businessId($request));
        try {
            $service->start($session);
            return redirect()->route('stock-taking-new.counts.sheet', $session)->with('status', 'Counting started.');
        } catch (\Throwable $exception) {
            return back()->withErrors($exception->getMessage());
        }
    }

    public function cancel(StockTakeSession $session, Request $request, TenantScopeService $scope)
    {
        $scope->assertBusinessRecord($session, $scope->businessId($request));
        abort_if(in_array($session->status, ['posted', 'cancelled'], true), 409);
        $session->update(['status' => 'cancelled', 'closed_at' => now()]);

        return back()->with('status', 'Session cancelled.');
    }
}
