<?php

namespace Modules\StockAdjustmentNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\StockAdjustmentNew\Entities\StockAdjustmentAccountMapping;
use Modules\StockAdjustmentNew\Services\SettingsReferenceService;
use Modules\StockAdjustmentNew\Services\StockAdjustmentSettingsService;
use Modules\StockAdjustmentNew\Services\TenantScopeService;

class SettingsController extends Controller
{
    public function index(
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
            static fn (array $category): bool => empty($category['parent_id'])
        ));
        $subCategories = array_values(array_filter(
            $allCategories,
            static fn (array $category): bool => ! empty($category['parent_id'])
        ));
        if ($categories === [] && $allCategories !== []) {
            $categories = $allCategories;
        }

        $accounts = $references->accounts($businessId);
        $accountGroups = $references->accountGroups($businessId);

        $mappings = StockAdjustmentAccountMapping::query()
            ->where('business_id', $businessId)
            ->latest('effective_from')
            ->latest('id')
            ->paginate(25, ['*'], 'mapping_page');

        // 8052: one visible mapping now carries separate Increase and Decrease
        // counterpart accounts. Decorate both new rows and older legacy rows so
        // the list/edit/view screens remain useful during a tenant-by-tenant
        // rollout without rewriting historical mapping records.
        $mappings->getCollection()->transform(function (StockAdjustmentAccountMapping $row) {
            return $this->decorateMapping($row);
        });

        $editingMapping = null;
        $editingId = (int) $request->query('edit_mapping', 0);
        if ($editingId > 0) {
            $editingMapping = $this->decorateMapping(
                StockAdjustmentAccountMapping::query()
                    ->where('business_id', $businessId)
                    ->whereKey($editingId)
                    ->firstOrFail()
            );
        }

        $savedMapping = null;
        $savedId = (int) $request->query('saved_mapping', 0);
        if ($savedId > 0) {
            $savedMapping = $this->decorateMapping(
                StockAdjustmentAccountMapping::query()
                    ->where('business_id', $businessId)
                    ->whereKey($savedId)
                    ->first()
            );
        }

        $viewingMapping = null;
        $viewingId = (int) $request->query('view_mapping', 0);
        if ($viewingId > 0) {
            $viewingMapping = $this->decorateMapping(
                StockAdjustmentAccountMapping::query()
                    ->where('business_id', $businessId)
                    ->whereKey($viewingId)
                    ->firstOrFail()
            );
        }

        $categoryNames = collect($allCategories)->pluck('name', 'id');
        $accountNames = collect($accounts)->mapWithKeys(static function (array $account): array {
            $label = trim(($account['code'] !== '' ? $account['code'] . ' - ' : '') . $account['name']);
            return [$account['id'] => $label];
        });
        $groupNames = collect($accountGroups)->pluck('name', 'id');

        return view('stockadjustmentnew::settings.index', compact(
            'settings',
            'categories',
            'subCategories',
            'accounts',
            'accountGroups',
            'mappings',
            'editingMapping',
            'savedMapping',
            'viewingMapping',
            'categoryNames',
            'accountNames',
            'groupNames'
        ));
    }

    public function updateGeneral(
        Request $request,
        TenantScopeService $scope,
        StockAdjustmentSettingsService $settingsService
    ): RedirectResponse {
        $businessId = $this->requireBusiness($scope->businessId($request));

        $data = $request->validateWithBag('generalSettings', [
            'number_prefix' => 'required|string|max:30',
            'number_padding' => 'required|integer|min:3|max:10',
            'default_adjustment_type' => ['required', Rule::in(['quantity', 'value', 'damage', 'expiry'])],
            'default_page_size' => ['required', Rule::in([10, 25, 50, 100])],
            'quantity_decimals' => 'required|integer|min:0|max:8',
            'amount_decimals' => 'required|integer|min:0|max:8',
            'require_reason' => 'nullable|boolean',
            'require_location' => 'nullable|boolean',
            'require_store' => 'nullable|boolean',
            'require_approval' => 'nullable|boolean',
            'auto_submit' => 'nullable|boolean',
            'auto_post_after_approval' => 'nullable|boolean',
            'require_batch_when_available' => 'nullable|boolean',
            'hide_zero_stock_products' => 'nullable|boolean',
            'allow_negative_stock' => 'nullable|boolean',
            'allow_zero_unit_cost' => 'nullable|boolean',
            'allow_backdated_adjustments' => 'nullable|boolean',
            'max_backdate_days' => 'nullable|integer|min:0|max:36500',
            'allow_future_dated_adjustments' => 'nullable|boolean',
            'batch_selection_method' => ['required', Rule::in(['fefo', 'fifo', 'manual'])],
        ]);

        $settingsService->save($businessId, $data, $scope->userId());

        return redirect()->route('stock-adjustment-new.settings.index')
            ->with('status', 'Stock Adjustment settings saved successfully.');
    }

    public function storeMapping(
        Request $request,
        TenantScopeService $scope,
        SettingsReferenceService $references
    ): RedirectResponse {
        $businessId = $this->requireBusiness($scope->businessId($request));
        $data = $this->validateMapping($request, $references, $businessId);

        $mapping = StockAdjustmentAccountMapping::query()->create($data + [
            'business_id' => $businessId,
            'created_by' => $scope->userId(),
            'updated_by' => $scope->userId(),
        ]);

        return redirect()->to(
            route('stock-adjustment-new.settings.index', ['saved_mapping' => $mapping->id]) . '#accounting-mapping-form'
        )->with('status', 'Stock Adjustment account mapping saved successfully. The selected values are shown below.');
    }

    public function updateMapping(
        Request $request,
        StockAdjustmentAccountMapping $mapping,
        TenantScopeService $scope,
        SettingsReferenceService $references
    ): RedirectResponse {
        $businessId = $this->requireBusiness($scope->businessId($request));
        $this->assertOwned($mapping, $businessId);
        $data = $this->validateMapping($request, $references, $businessId);

        $mapping->fill($data + ['updated_by' => $scope->userId()])->save();

        return redirect()->to(
            route('stock-adjustment-new.settings.index', ['saved_mapping' => $mapping->id]) . '#accounting-mapping-form'
        )->with('status', 'Stock Adjustment account mapping updated successfully. The saved values are shown below.');
    }

    /** @return array<string, mixed> */
    private function validateMapping(
        Request $request,
        SettingsReferenceService $references,
        int $businessId
    ): array {
        $data = $request->validateWithBag('mappingSettings', [
            'effective_from' => 'required|date',
            'category_id' => 'nullable|integer|min:1',
            'sub_category_id' => 'nullable|integer|min:1',
            'stock_account_group_id' => 'nullable|integer|min:1',
            'stock_account_id' => 'required|integer|min:1',
            'increase_account_id' => 'required|integer|min:1',
            'decrease_account_id' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ], [
            'stock_account_id.required' => 'Please select the Stock Account.',
            'increase_account_id.required' => 'Please select the Increase - Account to Link.',
            'decrease_account_id.required' => 'Please select the Decrease - Account to Link.',
        ]);

        // Resolve the hierarchy from one business-scoped category snapshot.
        // This prevents a stale Select2 value from being validated against a
        // different parent after the user changes Category.
        $categoryRows = collect($references->categories($businessId))->keyBy(
            static fn (array $category): int => (int) $category['id']
        );

        foreach (['category_id', 'sub_category_id'] as $field) {
            if (! empty($data[$field]) && ! $categoryRows->has((int) $data[$field])) {
                $this->throwMappingValidation([
                    $field => 'The selected category is invalid for this business.',
                ]);
            }
        }

        // If a valid sub category was selected while Category was still blank,
        // infer its real parent. The browser also does this now, but keeping the
        // same rule server-side makes saved mappings reliable for non-JS clients.
        if (! empty($data['sub_category_id'])) {
            $subCategory = $categoryRows->get((int) $data['sub_category_id']);
            $parentId = $subCategory['parent_id'] ?? null;

            if (empty($parentId)) {
                $this->throwMappingValidation([
                    'sub_category_id' => 'Please select a valid sub category.',
                ]);
            }

            if (empty($data['category_id'])) {
                $data['category_id'] = (int) $parentId;
            } elseif ((int) $parentId !== (int) $data['category_id']) {
                $this->throwMappingValidation([
                    'sub_category_id' => 'The selected sub category does not belong to the selected category.',
                ]);
            }
        }

        foreach (['stock_account_id', 'increase_account_id', 'decrease_account_id'] as $field) {
            if (! $references->accountBelongsToBusiness((int) $data[$field], $businessId)) {
                $this->throwMappingValidation([
                    $field => 'The selected account is invalid for this business.',
                ]);
            }
        }
        if (! empty($data['stock_account_group_id'])
            && ! $references->accountGroupBelongsToBusiness((int) $data['stock_account_group_id'], $businessId)) {
            $this->throwMappingValidation([
                'stock_account_group_id' => 'The selected Stock Account Group is invalid for this business.',
            ]);
        }

        $data['is_active'] = $request->boolean('is_active');

        // 8052 removes Adjustment Type from the mapping UI. Keep the legacy
        // column populated with its neutral value so existing indexes/data
        // contracts remain valid. account_to_link_id is deliberately cleared:
        // a single legacy counterpart cannot safely represent two different
        // direction accounts.
        $data['adjustment_type'] = 'quantity';
        $data['account_to_link_id'] = null;

        return $data;
    }

    private function decorateMapping(?StockAdjustmentAccountMapping $mapping): ?StockAdjustmentAccountMapping
    {
        if (! $mapping) {
            return null;
        }

        $mapping->setAttribute(
            'increase_account_display_id',
            $this->directionAccountId($mapping, 'increase')
        );
        $mapping->setAttribute(
            'decrease_account_display_id',
            $this->directionAccountId($mapping, 'decrease')
        );

        return $mapping;
    }

    private function directionAccountId(StockAdjustmentAccountMapping $mapping, string $direction): ?int
    {
        $directionColumn = $direction === 'decrease' ? 'decrease_account_id' : 'increase_account_id';
        $specific = (int) ($mapping->getAttribute($directionColumn) ?? 0);
        if ($specific > 0) {
            return $specific;
        }

        // Legacy rows had one Account to Link plus an Adjustment Type. Preserve
        // their meaning in the redesigned screen until each row is edited and
        // saved in the new two-account format.
        $legacy = (int) ($mapping->account_to_link_id ?? 0);
        if ($legacy <= 0) {
            return null;
        }

        $legacyType = strtolower((string) ($mapping->adjustment_type ?? 'quantity'));
        if (in_array($legacyType, ['increase', 'decrease'], true)) {
            return $legacyType === $direction ? $legacy : null;
        }

        return $legacy;
    }

    /** @param array<string, string|array<int, string>> $messages */
    private function throwMappingValidation(array $messages): void
    {
        $exception = ValidationException::withMessages($messages);
        $exception->errorBag = 'mappingSettings';
        throw $exception;
    }

    private function requireBusiness(?int $businessId): int
    {
        abort_unless($businessId, 422, 'A business must be selected before managing Stock Adjustment settings.');
        return (int) $businessId;
    }

    private function assertOwned(StockAdjustmentAccountMapping $mapping, int $businessId): void
    {
        abort_unless((int) $mapping->business_id === $businessId, 404);
    }
}
