<?php

namespace Modules\StockAdjustmentNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\StockAdjustmentNew\Entities\StockAdjustment;
use Modules\StockAdjustmentNew\Entities\StockAdjustmentReason;
use Modules\StockAdjustmentNew\Services\ProductBridgeService;
use Modules\StockAdjustmentNew\Services\SettingsReferenceService;
use Modules\StockAdjustmentNew\Services\StockAdjustmentService;
use Modules\StockAdjustmentNew\Services\StockAdjustmentSettingsService;
use Modules\StockAdjustmentNew\Services\TenantScopeService;

class StockAdjustmentController extends Controller
{
    public function index(
        Request $request,
        TenantScopeService $scope,
        StockAdjustmentSettingsService $settingsService
    ) {
        $businessId = $scope->businessId($request);
        $settings = $settingsService->values($businessId);
        $pageSize = max(10, min(100, (int) ($settings['default_page_size'] ?? 25)));

        $adjustments = StockAdjustment::withCount('lines')
            ->when($businessId, fn ($query) => $query->where('business_id', $businessId))
            ->latest('adjustment_date')
            ->latest('id')
            ->paginate($pageSize);

        return view('stockadjustmentnew::adjustments.index', compact('adjustments', 'settings'));
    }

    public function create(
        Request $request,
        TenantScopeService $scope,
        StockAdjustmentSettingsService $settingsService,
        SettingsReferenceService $references
    ) {
        $businessId = $this->requireBusiness($scope->businessId($request));
        $settings = $settingsService->values($businessId);
        $allCategories = $references->categories($businessId);
        $categories = array_values(array_filter(
            $allCategories,
            static fn (array $category): bool => $category['parent_id'] === null
        ));
        $subCategories = array_values(array_filter(
            $allCategories,
            static fn (array $category): bool => $category['parent_id'] !== null
        ));
        if ($categories === [] && $allCategories !== []) {
            $categories = $allCategories;
        }

        $permittedLocationIds = $scope->permittedLocationIds($request);
        $locations = $references->locations($businessId, $permittedLocationIds);
        $stores = $references->stores($businessId);
        $defaultLocationId = $scope->defaultLocationId($request, $locations);
        $defaultStoreId = $scope->defaultStoreId($request, $stores, $defaultLocationId);

        $reasons = StockAdjustmentReason::query()
            ->where('is_active', 1)
            ->where(function ($query) use ($businessId): void {
                $query->whereNull('business_id')->orWhere('business_id', $businessId);
            })
            ->orderBy('name')
            ->get();

        return view('stockadjustmentnew::adjustments.create', compact(
            'reasons',
            'settings',
            'categories',
            'subCategories',
            'locations',
            'stores',
            'defaultLocationId',
            'defaultStoreId'
        ));
    }

    public function store(
        Request $request,
        StockAdjustmentService $service,
        ProductBridgeService $products,
        TenantScopeService $scope,
        StockAdjustmentSettingsService $settingsService,
        SettingsReferenceService $references
    ) {
        $businessId = $this->requireBusiness($scope->businessId($request));
        $settings = $settingsService->values($businessId);

        $data = $request->validate([
            // A stock balance is location-specific. Location is therefore
            // always required even on tenants where the old setting was off.
            'location_id' => ['required', 'integer', 'min:1'],
            'store_id' => [(bool) ($settings['require_store'] ?? false) ? 'required' : 'nullable', 'integer', 'min:1'],
            'adjustment_date' => 'required|date',
            'adjustment_type' => ['required', Rule::in(['quantity', 'value', 'damage', 'expiry'])],
            'reason_id' => [(bool) ($settings['require_reason'] ?? false) ? 'required' : 'nullable', 'integer', 'min:1'],
            'notes' => 'nullable|string|max:5000',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer|min:1',
            'lines.*.variation_id' => 'nullable|integer|min:1',
            'lines.*.product_name' => 'nullable|string|max:191',
            'lines.*.sku' => 'nullable|string|max:191',
            'lines.*.system_qty' => 'nullable|numeric',
            'lines.*.counted_qty' => 'required|numeric',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
            'lines.*.batch_no' => 'nullable|string|max:191',
            'lines.*.expiry_date' => 'nullable|date',
            'lines.*.stock_adjustment_type' => ['required', Rule::in(['increase', 'decrease'])],
            'lines.*.line_notes' => 'nullable|string|max:2000',
        ], [
            'location_id.required' => 'Please select a Location before saving the stock adjustment.',
            'store_id.required' => 'Please select a Store before saving the stock adjustment.',
            'reason_id.required' => 'Please select an adjustment reason.',
            'lines.*.product_id.required' => 'Please search and select a product for every stock adjustment line.',
            'lines.*.product_id.min' => 'Please select a valid product for every stock adjustment line.',
            'lines.*.stock_adjustment_type.required' => 'Please select Increase or Decrease for every product line.',
        ]);

        $locationId = (int) $data['location_id'];
        $storeId = ! empty($data['store_id']) ? (int) $data['store_id'] : null;
        $permittedLocationIds = $scope->permittedLocationIds($request);

        if (! $references->locationBelongsToBusiness($locationId, $businessId, $permittedLocationIds)) {
            throw ValidationException::withMessages([
                'location_id' => 'The selected Location is invalid or is not assigned to this user.',
            ]);
        }
        if ($storeId !== null && ! $references->storeBelongsToBusiness($storeId, $businessId, $locationId)) {
            throw ValidationException::withMessages([
                'store_id' => 'The selected Store is invalid or does not belong to the selected Location.',
            ]);
        }

        $this->validateAdjustmentDate($data['adjustment_date'], $settings);

        if (! empty($data['reason_id'])) {
            $allowedReason = StockAdjustmentReason::query()
                ->where('id', $data['reason_id'])
                ->where(function ($query) use ($businessId): void {
                    $query->whereNull('business_id')->orWhere('business_id', $businessId);
                })
                ->exists();
            if (! $allowedReason) {
                throw ValidationException::withMessages(['reason_id' => 'The selected adjustment reason is invalid.']);
            }
        }

        foreach ($data['lines'] as $index => &$line) {
            $direction = (string) ($line['stock_adjustment_type'] ?? '');
            $line['stock_adjustment_type'] = $direction;
            $variationId = isset($line['variation_id']) ? (int) $line['variation_id'] : null;

            $selectedProduct = $products->findProduct(
                (int) $line['product_id'],
                $variationId,
                $businessId,
                $locationId,
                $storeId
            );

            if ($selectedProduct === null) {
                throw ValidationException::withMessages([
                    "lines.{$index}.product_id" => 'The selected product is invalid or is not available for this business.',
                ]);
            }

            if (
                $direction === 'decrease'
                && (bool) ($settings['hide_zero_stock_products'] ?? false)
                && (float) ($selectedProduct['system_qty'] ?? 0) <= 0
            ) {
                throw ValidationException::withMessages([
                    "lines.{$index}.product_id" => 'The selected product has no available stock and is hidden by the Stock Adjustment settings.',
                ]);
            }

            $line['product_name'] = $selectedProduct['name'];
            $line['sku'] = $selectedProduct['sku'];

            $availableBatches = $products->getAvailableBatches(
                (int) $line['product_id'],
                $variationId,
                $businessId,
                $locationId,
                $storeId,
                (string) ($settings['batch_selection_method'] ?? 'fefo')
            );

            if ($availableBatches !== []) {
                $submittedBatchNo = trim((string) ($line['batch_no'] ?? ''));
                $selectedBatch = null;

                if ($submittedBatchNo !== '') {
                    foreach ($availableBatches as $availableBatch) {
                        if (strcasecmp((string) $availableBatch['batch_no'], $submittedBatchNo) === 0) {
                            $selectedBatch = $availableBatch;
                            break;
                        }
                    }

                    if ($selectedBatch === null) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.batch_no" => 'The selected batch is no longer available.',
                        ]);
                    }
                } elseif ((bool) ($settings['require_batch_when_available'] ?? true)) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.batch_no" => 'Please select an available batch number for the selected product.',
                    ]);
                }

                if ($selectedBatch !== null) {
                    $line['batch_no'] = $selectedBatch['batch_no'];
                    $line['expiry_date'] = $selectedBatch['expiry_date'] ?? null;
                    $line['system_qty'] = (float) $selectedBatch['available_qty'];

                    if ((float) ($line['unit_cost'] ?? 0) === 0.0 && (float) ($selectedBatch['unit_cost'] ?? 0) > 0) {
                        $line['unit_cost'] = $selectedBatch['unit_cost'];
                    }
                } else {
                    $line['batch_no'] = null;
                    $line['expiry_date'] = null;
                    $line['system_qty'] = (float) ($selectedProduct['system_qty'] ?? 0);
                }
            } else {
                $line['batch_no'] = null;
                $line['expiry_date'] = null;
                $line['system_qty'] = (float) ($selectedProduct['system_qty'] ?? 0);
            }

            if ((float) ($line['unit_cost'] ?? 0) === 0.0 && (float) $selectedProduct['unit_cost'] > 0) {
                $line['unit_cost'] = $selectedProduct['unit_cost'];
            }

            $systemQty = (float) ($line['system_qty'] ?? 0);
            $countedQty = (float) ($line['counted_qty'] ?? 0);

            if (! (bool) ($settings['allow_negative_stock'] ?? false) && $countedQty < 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.counted_qty" => 'Negative counted quantities are disabled in Stock Adjustment settings.',
                ]);
            }

            if ($direction === 'increase' && $countedQty <= $systemQty) {
                throw ValidationException::withMessages([
                    "lines.{$index}.counted_qty" => 'For an Increase adjustment, Counted Qty must be greater than System Qty.',
                ]);
            }
            if ($direction === 'decrease' && $countedQty >= $systemQty) {
                throw ValidationException::withMessages([
                    "lines.{$index}.counted_qty" => 'For a Decrease adjustment, Counted Qty must be less than System Qty.',
                ]);
            }

            if (! (bool) ($settings['allow_zero_unit_cost'] ?? true) && (float) ($line['unit_cost'] ?? 0) <= 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.unit_cost" => 'Unit cost must be greater than zero according to Stock Adjustment settings.',
                ]);
            }
        }
        unset($line);

        $adjustment = $service->create($data, $data['lines'], $businessId, $scope->userId());

        return redirect()->route('stock-adjustment-new.adjustments.show', $adjustment)
            ->with('status', 'Stock adjustment draft saved successfully.');
    }

    public function show(
        Request $request,
        StockAdjustment $adjustment,
        TenantScopeService $scope,
        StockAdjustmentSettingsService $settingsService,
        SettingsReferenceService $references
    ) {
        $businessId = $this->requireBusiness($scope->businessId($request));
        $this->assertOwned($adjustment, $businessId);
        $adjustment->load(['lines', 'audits']);
        $settings = $settingsService->values($businessId);
        $locationRow = collect($references->locations($businessId, null))->firstWhere('id', (int) $adjustment->location_id);
        $storeRow = collect($references->stores($businessId))->firstWhere('id', (int) $adjustment->store_id);
        $locationName = is_array($locationRow) ? ($locationRow['name'] ?? null) : null;
        $storeName = is_array($storeRow) ? ($storeRow['name'] ?? null) : null;

        return view('stockadjustmentnew::adjustments.show', compact(
            'adjustment',
            'settings',
            'locationName',
            'storeName'
        ));
    }

    public function submit(Request $request, StockAdjustment $adjustment, StockAdjustmentService $service, TenantScopeService $scope)
    {
        $this->assertOwned($adjustment, $scope->businessId($request));
        try {
            $service->submit($adjustment, $scope->userId());
            return back()->with('status', 'Stock adjustment moved to the next workflow stage.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->with('error', $exception->getMessage());
        }
    }

    public function approve(Request $request, StockAdjustment $adjustment, StockAdjustmentService $service, TenantScopeService $scope)
    {
        $this->assertOwned($adjustment, $scope->businessId($request));
        try {
            $service->approve($adjustment, $scope->userId(), $request->input('remarks'));
            return back()->with('status', 'Stock adjustment approved.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->with('error', $exception->getMessage());
        }
    }

    public function reject(Request $request, StockAdjustment $adjustment, StockAdjustmentService $service, TenantScopeService $scope)
    {
        $this->assertOwned($adjustment, $scope->businessId($request));
        try {
            $service->reject($adjustment, $scope->userId(), $request->input('remarks'));
            return back()->with('status', 'Stock adjustment rejected.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->with('error', $exception->getMessage());
        }
    }

    public function post(Request $request, StockAdjustment $adjustment, StockAdjustmentService $service, TenantScopeService $scope)
    {
        $this->assertOwned($adjustment, $scope->businessId($request));
        try {
            $service->post($adjustment, $scope->userId());
            return back()->with('status', 'Stock adjustment posted. Stock quantities and mapped accounts were updated successfully.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->with('error', 'Posting was not completed: ' . $exception->getMessage());
        }
    }

    /** @param array<string, mixed> $settings */
    private function validateAdjustmentDate(string $value, array $settings): void
    {
        $date = Carbon::parse($value)->startOfDay();
        $today = now()->startOfDay();

        if ($date->lt($today) && ! (bool) ($settings['allow_backdated_adjustments'] ?? true)) {
            throw ValidationException::withMessages([
                'adjustment_date' => 'Backdated stock adjustments are disabled in Stock Adjustment settings.',
            ]);
        }

        $maxBackdateDays = $settings['max_backdate_days'] ?? null;
        if ($date->lt($today) && $maxBackdateDays !== null
            && $date->lt($today->copy()->subDays((int) $maxBackdateDays))) {
            throw ValidationException::withMessages([
                'adjustment_date' => 'The adjustment date exceeds the maximum permitted backdate period.',
            ]);
        }

        if ($date->gt($today) && ! (bool) ($settings['allow_future_dated_adjustments'] ?? true)) {
            throw ValidationException::withMessages([
                'adjustment_date' => 'Future-dated stock adjustments are disabled in Stock Adjustment settings.',
            ]);
        }
    }

    private function requireBusiness(?int $businessId): int
    {
        abort_unless($businessId, 422, 'A business must be selected before creating a Stock Adjustment.');
        return (int) $businessId;
    }

    private function assertOwned(StockAdjustment $adjustment, ?int $businessId): void
    {
        abort_unless((int) $adjustment->business_id === (int) $businessId, 404);
    }
}
