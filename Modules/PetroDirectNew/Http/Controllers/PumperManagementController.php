<?php

namespace Modules\PetroDirectNew\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PetroDirectNew\Entities\PdirectnewAssignment;
use Modules\PetroDirectNew\Entities\PdirectnewOperator;
use Modules\PetroDirectNew\Entities\PdirectnewShift;
use Modules\PetroDirectNew\Services\OperatorImportService;
use Modules\PetroDirectNew\Services\PumperManagementService;
use Modules\PetroDirectNew\Services\SharedMasterDataService;
use Modules\PetroDirectNew\Support\BusinessContext;
use Modules\PetroDirectNew\Support\PermissionGate;

class PumperManagementController extends Controller
{
    private const TABS = [
        'operators',
        'payments',
        'pumper_day_entries',
        'shift_summary',
        'payment_summary',
        'meters_with_payments',
        'daily_pump_status',
        'close_shift',
        'current_meter',
        'unload_stock',
    ];

    public function __construct(
        private PumperManagementService $service,
        private SharedMasterDataService $master,
        private OperatorImportService $operatorImport,
        private BusinessContext $context
    ) {
    }

    public function index(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.pumpers.view');
        $tab = $this->normalizeTab($request->string('tab')->toString());
        $locationId = $request->integer('location_id') ?: null;

        // A lightweight freshness check avoids a full source synchronization on
        // every page request. The normal operator list is then one optimized query.
        $syncResult = $tab === 'operators'
            ? $this->operatorImport->ensureFresh($locationId)
            : ['available' => true, 'created' => 0, 'updated' => 0, 'success' => true, 'skipped' => true];

        return view('petrodirectnew::pumper_management.index', array_merge(
            $this->service->workspace($tab, $locationId),
            $this->masterDataForTab($tab),
            [
                'locations' => $this->master->locations(),
                'locationId' => $locationId,
                'operatorSyncResult' => $syncResult,
            ]
        ));
    }

    public function tab(Request $request, string $tab)
    {
        PermissionGate::authorize('petro_direct_new.pumpers.view');
        $tab = $this->normalizeTab($tab);
        $locationId = $request->integer('location_id') ?: null;

        if ($tab === 'operators') {
            $this->operatorImport->ensureFresh($locationId);
        }

        $data = array_merge(
            $this->service->workspace($tab, $locationId),
            $this->masterDataForTab($tab),
            [
                'locations' => $this->master->locations(),
                'locationId' => $locationId,
            ]
        );

        return response()->json([
            'tab' => $tab,
            'html' => view('petrodirectnew::pumper_management.tabs.' . $tab, $data)->render(),
        ]);
    }

    public function syncOperators(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.operators.manage');
        $locationId = $request->integer('location_id') ?: null;
        $result = $this->operatorImport->sync($locationId);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => sprintf('Pump Operators synchronized: %d added, %d updated.', $result['created'], $result['updated']),
                'result' => $result,
            ]);
        }

        return back()->with('status', sprintf(
            'Pump Operators synchronized: %d added, %d updated.',
            $result['created'],
            $result['updated']
        ));
    }

    private function normalizeTab(?string $tab): string
    {
        $tab = $tab ?: 'operators';
        return in_array($tab, self::TABS, true) ? $tab : 'operators';
    }

    private function masterDataForTab(string $tab): array
    {
        return [
            'products' => $tab === 'unload_stock' ? $this->master->products() : collect(),
            // User choices are loaded on demand from the operator popup so a
            // business with thousands of users does not slow the list page.
            'users' => collect(),
            'contacts' => $tab === 'unload_stock' ? $this->master->contacts() : collect(),
        ];
    }

    public function searchOperatorUsers(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.operators.manage');
        $rows = $this->master->searchUsers($request->string('q')->toString(), 30);

        return response()->json([
            'results' => $rows->map(function ($user) {
                $name = trim((string) (($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));
                $text = $name ?: ($user->username ?? $user->email ?? ('User ' . $user->id));
                if (!empty($user->username) && $name !== '') {
                    $text .= ' (' . $user->username . ')';
                }
                return ['id' => (int) $user->id, 'text' => $text];
            })->values(),
        ]);
    }

    public function storeOperator(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.operators.manage');
        $operator = $this->service->storeOperator($this->validatedOperator($request));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pump Operator saved successfully.',
                'operator' => $this->service->operatorDetails($operator->id),
            ], 201);
        }

        return back()->with('status', 'Pump Operator saved successfully.');
    }

    public function showOperator(Request $request, int $id)
    {
        PermissionGate::authorize('petro_direct_new.pumpers.view');
        return response()->json([
            'success' => true,
            'operator' => $this->service->operatorDetails($id),
        ]);
    }

    public function updateOperator(Request $request, int $id)
    {
        PermissionGate::authorize('petro_direct_new.operators.manage');
        $operator = PdirectnewOperator::where('business_id', $this->context->requireBusiness())->findOrFail($id);
        $operator = $this->service->updateOperator($operator, $this->validatedOperator($request, true));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pump Operator updated successfully.',
                'operator' => $this->service->operatorDetails($operator->id),
            ]);
        }

        return back()->with('status', 'Pump Operator updated successfully.');
    }

    public function toggleOperator(Request $request, int $id)
    {
        PermissionGate::authorize('petro_direct_new.operators.manage');
        $operator = PdirectnewOperator::where('business_id', $this->context->requireBusiness())->findOrFail($id);
        $operator = $this->service->toggleOperator($operator);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $operator->is_active ? 'Pump Operator activated.' : 'Pump Operator deactivated.',
            ]);
        }

        return back()->with('status', 'Operator status updated.');
    }

    public function destroyOperator(Request $request, int $id)
    {
        PermissionGate::authorize('petro_direct_new.operators.manage');
        $operator = PdirectnewOperator::where('business_id', $this->context->requireBusiness())->findOrFail($id);
        $this->service->deactivateOperator($operator);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pump Operator deactivated safely. Historical transactions were preserved.',
            ]);
        }

        return back()->with('status', 'Pump Operator deactivated safely.');
    }

    private function validatedOperator(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'location_id' => 'required|integer',
            'user_id' => 'nullable|integer',
            'operator_no' => 'nullable|string|max:80',
            'name' => 'required|string|max:190',
            'address' => 'nullable|string|max:1000',
            'mobile' => 'nullable|string|max:50',
            'landline' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'nic' => 'nullable|string|max:80',
            'email' => 'nullable|email|max:190',
            'username' => 'nullable|string|max:100',
            'passcode' => ($isUpdate ? 'nullable' : 'nullable') . '|string|min:4|max:64',
            'opening_balance' => 'nullable|numeric',
            'commission_type' => 'nullable|in:none,fixed,percentage',
            'commission_value' => 'nullable|numeric|min:0',
            'short_amount' => 'nullable|numeric|min:0',
            'excess_amount' => 'nullable|numeric|min:0',
            'transaction_date' => 'nullable|date',
            'can_login' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'can_fullscreen' => 'nullable|boolean',
            'hide_in_direct_settlement_if_pending_shifts' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];

        $data = $request->validate($rules);
        foreach (['can_login', 'is_default', 'can_fullscreen', 'hide_in_direct_settlement_if_pending_shifts', 'is_active'] as $field) {
            $data[$field] = $request->boolean($field);
        }
        return $data;
    }

    public function storeAdjustment(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.payments.manage');
        $data = $request->validate([
            'location_id' => 'required|integer',
            'operator_id' => 'required|integer',
            'settlement_id' => 'nullable|integer',
            'adjustment_type' => 'required|in:shortage,excess,shortage_recovery,excess_commission',
            'amount' => 'required|numeric|min:0.0001',
            'reason' => 'required|string|max:1000',
        ]);
        $this->service->storeAdjustment($data);
        return $this->completed($request, 'Pumper excess/shortage payment saved.');
    }

    public function storePumperDayEntry(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.day_entries.manage');
        $data = $request->validate([
            'location_id' => 'required|integer',
            'operator_id' => 'required|integer',
            'shift_id' => 'nullable|integer',
            'entry_date' => 'required|date',
            'entry_type' => 'required|string|max:50',
            'reference_no' => 'nullable|string|max:190',
            'amount' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);
        $this->service->storePumperDayEntry($data);
        return $this->completed($request, 'Pumper day entry saved.');
    }

    public function storeTank(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.tanks.manage');
        $data = $request->validate([
            'location_id' => 'required|integer',
            'tank_no' => 'nullable|string|max:80',
            'name' => 'required|string|max:190',
            'product_id' => 'nullable|integer',
            'capacity' => 'required|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:30',
        ]);
        $this->service->storeTank($data);
        return $this->completed($request, 'Fuel tank saved.');
    }

    public function storePump(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.pumps.manage');
        $data = $request->validate([
            'location_id' => 'required|integer',
            'pump_no' => 'nullable|string|max:80',
            'name' => 'required|string|max:190',
            'tank_id' => 'nullable|integer',
            'product_id' => 'nullable|integer',
            'meter_type' => 'nullable|string|max:30',
            'opening_meter' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:30',
        ]);
        $this->service->storePump($data);
        return $this->completed($request, 'Pump saved.');
    }

    public function assign(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.assignments.manage');
        $data = $request->validate([
            'location_id' => 'required|integer',
            'shift_id' => 'nullable|integer',
            'operator_id' => 'required|integer',
            'pump_id' => 'required|integer',
        ]);
        $this->service->assign($data);
        return $this->completed($request, 'Pump assigned.');
    }

    public function recordMeter(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.meters.manage');
        $data = $request->validate([
            'location_id' => 'required|integer',
            'shift_id' => 'nullable|integer',
            'assignment_id' => 'nullable|integer',
            'pump_id' => 'required|integer',
            'operator_id' => 'nullable|integer',
            'reading_type' => 'nullable|in:opening,current,closing,testing',
            'reading' => 'required|numeric|min:0',
            'testing_qty' => 'nullable|numeric|min:0',
        ]);
        $this->service->recordMeter($data);
        return $this->completed($request, 'Meter reading saved.');
    }

    public function closeAssignment(Request $request, int $id)
    {
        PermissionGate::authorize('petro_direct_new.assignments.manage');
        $data = $request->validate([
            'closing_meter' => 'required|numeric|min:0',
            'testing_qty' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
        ]);
        $assignment = PdirectnewAssignment::where('business_id', $this->context->requireBusiness())->findOrFail($id);
        $this->service->closeAssignment($assignment, $data);
        return $this->completed($request, 'Pump assignment closed.');
    }

    public function storeUnloadStock(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.unload_stock.manage');
        $data = $request->validate([
            'location_id' => 'required|integer',
            'operator_id' => 'nullable|integer',
            'shift_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer',
            'reference_no' => 'nullable|string|max:190',
            'unload_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
            'lines' => 'required|array|min:1',
            'lines.*.tank_id' => 'nullable|integer',
            'lines.*.product_id' => 'nullable|integer',
            'lines.*.quantity' => 'nullable|numeric|min:0',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
        ]);
        $this->service->storeUnloadStock($data);
        return $this->completed($request, 'Unload stock saved and tank stock updated.');
    }

    public function storeDip(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.dips.manage');
        $data = $request->validate([
            'tank_id' => 'required|integer',
            'dip_chart_id' => 'nullable|integer',
            'reading_date' => 'required|date',
            'dip_value' => 'required|numeric|min:0',
            'calculated_stock' => 'nullable|numeric|min:0',
            'actual_stock' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);
        $this->service->storeDipReading($data);
        return $this->completed($request, 'Dip reading saved.');
    }

    public function storeTransfer(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.transfers.manage');
        $data = $request->validate([
            'from_tank_id' => 'required|integer',
            'to_tank_id' => 'required|integer|different:from_tank_id',
            'quantity' => 'required|numeric|min:0.001',
            'transfer_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
        ]);
        $this->service->storeTankTransfer($data);
        return $this->completed($request, 'Tank transfer completed.');
    }

    public function generateCollection(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.collections.manage');
        $data = $request->validate([
            'location_id' => 'required|integer',
            'collection_date' => 'required|date',
            'operator_id' => 'nullable|integer',
            'shift_id' => 'nullable|integer',
            'note' => 'nullable|string|max:1000',
        ]);
        $this->service->generateCollection($data);
        return $this->completed($request, 'Daily collection prepared.');
    }

    public function closeShift(Request $request, int $id)
    {
        PermissionGate::authorize('petro_direct_new.shifts.close');
        $shift = PdirectnewShift::where('business_id', $this->context->requireBusiness())->findOrFail($id);
        $this->service->closeShift($shift);
        return $this->completed($request, 'Shift closed.');
    }

    private function completed(Request $request, string $message, array $payload = [])
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge([
                'success' => true,
                'message' => $message,
            ], $payload));
        }

        return back()->with('status', $message);
    }
}
