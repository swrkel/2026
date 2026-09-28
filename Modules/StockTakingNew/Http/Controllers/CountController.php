<?php

namespace Modules\StockTakingNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Entities\StockTakeSession;
use Modules\StockTakingNew\Http\Requests\SaveCountsRequest;
use Modules\StockTakingNew\Services\CountService;
use Modules\StockTakingNew\Services\MasterDataBridgeService;
use Modules\StockTakingNew\Services\TenantScopeService;

class CountController extends Controller
{
    public function sheet(
        StockTakeSession $session,
        Request $request,
        TenantScopeService $scope,
        MasterDataBridgeService $masters
    ) {
        $scope->assertBusinessRecord($session, $scope->businessId($request));
        abort_unless(in_array($session->status, ['counting', 'recount'], true), 409, 'Start the session before opening the count sheet.');
        if ($session->status === 'recount') {
            abort_unless(auth()->user()?->can('stock_taking_new.recounts.manage'), 403);
        } else {
            abort_unless(auth()->user()?->can('stock_taking_new.counts.enter'), 403);
        }

        $query = $session->lines()
            ->when($request->filled('search'), function ($builder) use ($request): void {
                $term = '%' . $request->search . '%';
                $builder->where(fn ($nested) => $nested->where('product_name', 'like', $term)
                    ->orWhere('sku', 'like', $term)->orWhere('barcode', 'like', $term));
            });

        if ($request->filled('state')) {
            match ($request->state) {
                'pending' => $query->where('is_counted', 0),
                'counted' => $query->where('is_counted', 1),
                'recount' => $query->where('requires_recount', 1),
                default => null,
            };
        } elseif ($session->status === 'recount') {
            $query->where('requires_recount', 1);
        }

        $lines = $query->orderByDesc('requires_recount')->orderBy('product_name')
            ->paginate(100)->withQueryString();
        $locationName = $masters->locationName($session->location_id);
        $storeName = $masters->storeName($session->store_id);
        $hasRequiredRecounts = $session->lines()->where('requires_recount', 1)->exists();

        return view('stocktakingnew::counts.sheet', compact(
            'session', 'lines', 'locationName', 'storeName', 'hasRequiredRecounts'
        ));
    }

    public function save(
        StockTakeSession $session,
        SaveCountsRequest $request,
        TenantScopeService $scope,
        CountService $service
    ) {
        $scope->assertBusinessRecord($session, $scope->businessId($request));
        try {
            $saved = $service->save($session, $request->validated('counts'), false);
            return back()->with('status', "{$saved} count lines saved.");
        } catch (\Throwable $exception) {
            return back()->withErrors($exception->getMessage());
        }
    }

    public function saveRecount(
        StockTakeSession $session,
        SaveCountsRequest $request,
        TenantScopeService $scope,
        CountService $service
    ) {
        $scope->assertBusinessRecord($session, $scope->businessId($request));
        try {
            $saved = $service->save($session, $request->validated('counts'), true);
            return back()->with('status', "{$saved} recount lines saved.");
        } catch (\Throwable $exception) {
            return back()->withErrors($exception->getMessage());
        }
    }

    public function submit(
        StockTakeSession $session,
        Request $request,
        TenantScopeService $scope,
        CountService $service
    ) {
        $scope->assertBusinessRecord($session, $scope->businessId($request));
        try {
            $result = $service->submit($session);
            if ($result === 'recount') {
                return redirect()->route('stock-taking-new.counts.sheet', $session)
                    ->with('status', 'Initial count completed. Enter the required recount quantities.');
            }
            if ($result === 'approved') {
                return redirect()->route('stock-taking-new.sessions.show', $session)
                    ->with('status', 'Counts completed and automatically approved.');
            }
            return redirect()->route('stock-taking-new.sessions.show', $session)
                ->with('status', 'Counts submitted for approval.');
        } catch (\Throwable $exception) {
            return back()->withErrors($exception->getMessage());
        }
    }
}
