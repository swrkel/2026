<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPDNew\Http\Requests\OperatorMappingUpdateRequest;
use Modules\PetroPDNew\Services\PdnewBusinessFeatureService;
use Modules\PetroPDNew\Services\PdnewOperatorMappingService;
use Modules\PetroPDNew\Services\PdnewOperatorWorkspaceCacheService;
use Modules\PetroPDNew\Services\PdnewOperatorWorkspaceService;
use Modules\PumperDashboardNew\Services\PoneOperatorManagementService;
use RuntimeException;

class OperatorMappingController extends PdnewController
{
    public function index(
        Request $request,
        PdnewOperatorWorkspaceService $workspace,
        PdnewBusinessFeatureService $features,
        PdnewOperatorMappingService $mappingService,
        PoneOperatorManagementService $poneOperators,
        PdnewOperatorWorkspaceCacheService $workspaceCache
    ) {
        $businessId = $this->context->businessId();
        $locationId = $this->context->locationId();
        [$tabs, $tabVisibility, $activeTab] = $this->resolveTab($request, $workspace, $features, $businessId);

        $operatorSyncWarning = null;
        if ($activeTab === 'pump_operators' && $this->operatorDirectoryIsStale($businessId, $locationId)) {
            try {
                $this->synchronizeFromPone($businessId, $locationId, $mappingService, $poneOperators);
                $workspaceCache->flush($businessId);
            } catch (\Throwable $exception) {
                report($exception);
                $operatorSyncWarning = 'Operator synchronization could not finish. Run the supplied operator compatibility repair once, then synchronize again.';
            }
        }

        $filters = $this->filters($request, $activeTab);
        $workspaceData = $workspace->load($activeTab, $businessId, $locationId, $filters);
        $operatorOptions = $workspace->operatorOptions($businessId, $locationId);

        return view('petropdnew::operators.index', compact(
            'tabs',
            'tabVisibility',
            'activeTab',
            'filters',
            'workspaceData',
            'operatorOptions',
            'operatorSyncWarning'
        ));
    }

    /**
     * Render only the selected workspace tab for fast in-page navigation.
     */
    public function tab(
        Request $request,
        PdnewOperatorWorkspaceService $workspace,
        PdnewBusinessFeatureService $features
    ): JsonResponse {
        $businessId = $this->context->businessId();
        $locationId = $this->context->locationId();
        [, , $activeTab] = $this->resolveTab($request, $workspace, $features, $businessId);
        $filters = $this->filters($request, $activeTab);
        $workspaceData = $workspace->load($activeTab, $businessId, $locationId, $filters);

        $html = view('petropdnew::operators.tabs.' . $activeTab, compact(
            'activeTab',
            'filters',
            'workspaceData'
        ))->render();

        return response()->json([
            'ok' => true,
            'tab' => $activeTab,
            'html' => $html,
            'generated_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function sync(
        Request $request,
        PdnewOperatorMappingService $mappingService,
        PoneOperatorManagementService $poneOperators,
        PdnewOperatorWorkspaceCacheService $workspaceCache
    ) {
        try {
            $businessId = $this->context->businessId();
            $count = $this->synchronizeFromPone(
                $businessId,
                $this->context->locationId(),
                $mappingService,
                $poneOperators
            );

            $workspaceCache->flush($businessId);
            $message = $count . ' PD operator mapping(s) synchronized from Pumper Dashboard-New.';
            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $exception) {
            if ($request->expectsJson()) {
                report($exception);
                return response()->json(['ok' => false, 'message' => 'Operator synchronization could not finish. Please run the operator compatibility repair and retry.'], 422);
            }

            report($exception);
            return back()->with('error', 'Operator synchronization could not finish. Please run the operator compatibility repair and retry.');
        }
    }

    public function update(
        OperatorMappingUpdateRequest $request,
        int $operator,
        PdnewOperatorMappingService $service,
        PdnewOperatorWorkspaceCacheService $workspaceCache
    ) {
        try {
            $service->updateLocalSettings($this->operatorMapping($operator), $request->validated());
            $workspaceCache->flush($this->context->businessId());

            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'message' => 'PD operator mapping settings updated.']);
            }

            return back()->with('success', 'PD operator mapping settings updated.');
        } catch (\Throwable $exception) {
            if ($request->expectsJson()) {
                report($exception);
                return response()->json(['ok' => false, 'message' => 'Operator synchronization could not finish. Please run the operator compatibility repair and retry.'], 422);
            }

            return $this->error($exception);
        }
    }

    /** @return array{0:array<string,array<string,mixed>>,1:array<string,bool>,2:string} */
    private function resolveTab(
        Request $request,
        PdnewOperatorWorkspaceService $workspace,
        PdnewBusinessFeatureService $features,
        int $businessId
    ): array {
        $tabs = $workspace->tabs();
        $tabVisibility = $features->operatorTabs($businessId);
        $activeTab = (string) $request->query('tab', 'pump_operators');

        if (! array_key_exists($activeTab, $tabs)) {
            $activeTab = 'pump_operators';
        }

        if (! ($tabVisibility[$activeTab] ?? true)) {
            $activeTab = collect(array_keys($tabs))
                ->first(fn (string $tab): bool => (bool) ($tabVisibility[$tab] ?? true));
            abort_unless($activeTab, 403, 'All PD Operator tabs are disabled for this business.');
        }

        $features->authorizeOperatorTab($activeTab, $businessId);

        return [$tabs, $tabVisibility, $activeTab];
    }

    /** @return array<string,mixed> */
    private function filters(Request $request, string $activeTab): array
    {
        $todayTabs = ['pump_operators', 'daily_pump_status', 'current_meter', 'close_shift'];
        $defaultDate = in_array($activeTab, $todayTabs, true) ? now()->toDateString() : '';

        return [
            'search' => trim((string) $request->query('search', '')),
            'date_from' => $this->validDate((string) $request->query('date_from', $defaultDate)),
            'date_to' => $this->validDate((string) $request->query('date_to', $defaultDate)),
            'operator_profile_id' => (int) $request->query('operator_profile_id', 0) ?: null,
            'status' => trim((string) $request->query('status', '')),
            'payment_type' => trim((string) $request->query('payment_type', '')),
            'location_id' => $this->context->locationId(),
        ];
    }

    private function operatorDirectoryIsStale(int $businessId, ?int $locationId): bool
    {
        if (! Schema::hasTable('pone_pd_operators') || ! Schema::hasTable('pdnew_operator_mappings')) {
            return true;
        }

        $source = DB::table('pone_pd_operators')->where('business_id', $businessId);
        $target = DB::table('pdnew_operator_mappings')->where('business_id', $businessId);

        if ($locationId) {
            $source->where(function ($query) use ($locationId): void {
                $query->where('location_id', $locationId)->orWhereNull('location_id');
            });
            $target->where(function ($query) use ($locationId): void {
                $query->where('location_id', $locationId)->orWhereNull('location_id');
            });
        }

        $sourceCount = (int) (clone $source)->count();
        $targetCount = (int) (clone $target)->count();
        if ($sourceCount !== $targetCount || $sourceCount === 0) {
            return true;
        }

        $sourceUpdated = (string) ((clone $source)->max('updated_at') ?? '');
        $targetUpdated = (string) ((clone $target)->max('last_synced_at') ?? '');

        return $sourceUpdated !== '' && ($targetUpdated === '' || $sourceUpdated > $targetUpdated);
    }

    private function synchronizeFromPone(
        int $businessId,
        ?int $locationId,
        PdnewOperatorMappingService $mappingService,
        PoneOperatorManagementService $poneOperators
    ): int {
        if (! Schema::hasTable('pone_pd_operators')) {
            throw new RuntimeException('The Pumper Dashboard-New operator table is not installed in this tenant database.');
        }
        if (! Schema::hasTable('pdnew_operator_mappings')) {
            throw new RuntimeException('The Petro PD-New operator mapping table is not installed in this tenant database.');
        }

        $poneOperators->sync($businessId);

        $query = DB::table('pone_pd_operators')->where('business_id', $businessId);
        if ($locationId) {
            $query->where(function ($scope) use ($locationId): void {
                $scope->where('location_id', $locationId)->orWhereNull('location_id');
            });
        }

        $count = 0;
        foreach ($query->orderBy('id')->cursor() as $operator) {
            $operatorLocationId = (int) ($operator->location_id ?? 0) ?: null;
            if ($operatorLocationId) {
                $this->context->authorizeLocation($operatorLocationId);
            }

            $mappingService->syncFromPoneProfile($businessId, $operator);
            $count++;
        }

        return $count;
    }

    private function validDate(string $date): ?string
    {
        if ($date === '') {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date ? $date : null;
    }
}
