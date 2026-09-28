<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\RiceMill\Models\{Setting,PaddyVariety,Mill,RiceProduct,PackagingMaterial,PackagingMaterialMapping};
use Modules\RiceMill\Services\{NumberSeriesService,TenantContext,ExternalMasterDataService,LocationAccessService,PackagingMaterialProductSyncService,OutputTypeProductService};

class SettingsController extends BaseController
{
    public function __construct(
        TenantContext $context,
        private NumberSeriesService $numbers,
        private ExternalMasterDataService $masters,
        private LocationAccessService $locationAccess,
        private PackagingMaterialProductSyncService $packagingProductSync,
        private OutputTypeProductService $outputTypeProducts
    ) {
        parent::__construct($context);
    }

    public function index()
    {
        $b = $this->bid();
        $setting = Setting::forBusiness($b)->select(['id','business_id','settings'])->first();
        $storedSettings = (array) optional($setting)->settings;
        $settings = array_merge($this->defaults(), $storedSettings);
        // IS2293 compatibility: older mappings could save a Sub Category id in
        // the Product Category field. Resolve it to the top-level Category at
        // read time so the corrected Category-only dropdown and all Products in
        // that category tree work immediately without manual data repair.
        foreach (['paddy','rice'] as $mappingType) {
            $key = $mappingType . '_product_category_id';
            if (! empty($settings[$key])) {
                $settings[$key] = $this->masters->topLevelProductCategoryId($b, (int) $settings[$key]);
            }
        }
        $prefix = $this->prefix($settings);
        $purchaseOpening = max(1, (int) ($settings['paddy_purchase_opening_number'] ?? 1));
        $purchaseHasTransactions = DB::table('rcm_paddy_purchases')
            ->where('business_id', $b)
            ->exists();

        // Load number-series rows once for the whole Settings page. Previously
        // every Paddy Variety performed two extra queries and the three document
        // previews queried the same table again after each save redirect.
        $seriesByType = DB::table('rcm_number_series')
            ->where('business_id', $b)
            ->get()
            ->keyBy('series_type');

        $purchaseOpeningSaved = (bool) ($storedSettings['paddy_purchase_opening_saved'] ?? false)
            || $purchaseHasTransactions
            || $seriesByType->has('paddy_purchase');

        $varietyColumns = ['id','business_id','code','name','default_moisture_percent','foreign_matter_limit_percent','expected_rice_yield_percent','expected_broken_rice_percent','expected_bran_percent','expected_husk_percent','expected_process_loss_percent','quality_grade','lot_opening_number','active'];
        // Backward compatibility: tenants that have not yet imported the
        // Paddy Product link SQL must still be able to open Settings. The
        // Add/Edit form can match older rows by Product Name + SKU until the
        // optional permanent paddy_product_id column is available.
        if ($this->hasPaddyProductLinkColumn()) {
            array_splice($varietyColumns, 2, 0, ['paddy_product_id']);
        }
        $varieties = PaddyVariety::forBusiness($b)->orderBy('name')->get($varietyColumns);
        $usedVarietyIds = $this->usedVarietyIds($b);
        foreach ($varieties as $variety) {
            $opening = max(1, (int) ($variety->lot_opening_number ?: 1));
            $seriesType = $this->numbers->varietyLotType((int) $variety->id);
            $seriesPrefix = $this->numbers->varietyLotPrefix($prefix, (string) $variety->code);
            $seriesRow = $seriesByType->get($seriesType);
            $series = $this->seriesSnapshot($seriesRow, $seriesPrefix, $opening);

            $variety->setAttribute('lot_sequence_prefix', $series['prefix']);
            $variety->setAttribute('lot_next_number', $series['next_number']);
            $variety->setAttribute('lot_next_preview', $series['preview']);
            $variety->setAttribute(
                'lot_sequence_used',
                $seriesRow && (int) $seriesRow->next_number > $opening
            );
            $variety->setAttribute('has_transactions', in_array((int) $variety->id, $usedVarietyIds, true));
        }

        // Mill Settings is an administrative setup page. Show every active
        // Business Location belonging to THIS business in the already-initialized
        // tenant DB. Do not hide valid business locations merely because the
        // current user's operational location filter is narrower.
        $locations = $this->locationAccess->businessOptions($b);
        $locationNames = [];
        foreach ($locations as $location) {
            $locationNames[(int) $location['id']] = (string) $location['name'];
        }

        // A settings administrator must be able to manage every Mill belonging
        // to the active business, including Mills attached to another valid
        // location of the same tenant/business.
        $mills = Mill::forBusiness($b)->orderBy('name')->get(['id','business_id','location_id','code','name','capacity_per_hour','active']);
        $usedMillIds = DB::table('rcm_production_batches')->where('business_id', $b)->whereNotNull('mill_id')->distinct()->pluck('mill_id')->map(fn($x)=>(int)$x)->all();
        foreach ($mills as $mill) {
            $mill->setAttribute('has_transactions', in_array((int) $mill->id, $usedMillIds, true));
        }

        $productColumns = ['id','business_id','code','name','rice_type','paddy_variety_id','current_qty','active'];
        if ($this->hasRiceProductLinkColumn()) {
            array_splice($productColumns, 2, 0, ['products_new_product_id']);
        }
        $products = RiceProduct::forBusiness($b)->orderBy('name')->get($productColumns);
        $varietyNames = $varieties->mapWithKeys(fn($v) => [(int) $v->id => trim(($v->code ? $v->code . ' - ' : '') . $v->name)])->all();
        $usedProductIds = $this->usedProductIds($b);
        foreach ($products as $product) {
            $product->setAttribute('has_transactions', in_array((int) $product->id, $usedProductIds, true));
        }

        $purchaseSeries = $this->seriesSnapshot($seriesByType->get('paddy_purchase'), $prefix . '-PUR-', $purchaseOpening);
        $weighbridgeSeries = $this->seriesSnapshot($seriesByType->get('weighbridge'), $prefix . '-WB-', 1);
        $receiptSeries = $this->seriesSnapshot($seriesByType->get('paddy_receipt'), $prefix . '-RCV-', 1);

        // IS2289-2: Product Category Mapping is the first Settings tab. The
        // shared Products/Finance masters are read only through the module
        // adapter; selected IDs remain stored in rcm_settings.settings JSON.
        $productCategories = $this->masters->productCategories($b);
        $currentLiabilityAccounts = $this->masters->currentLiabilityAccounts($b);

        // Products New master data for the optional Packaging Material selector.
        // Category and Sub Category are filters only; Product is the saved master.
        $packagingCategoryHierarchy = $this->masters->productCategoryHierarchy($b);
        $packagingProductOptions = $this->masters->productsNewProducts($b);
        // Resolve the explicitly saved Packaging Material Product mappings
        // first. All Packaging Material displays in Settings must be limited to
        // these mapped Products only; older/manual rcm_packaging_materials rows
        // stay in the database but are not exposed in this Settings workflow.
        $packagingMaterialSelections = $this->packagingProductSync->syncSavedSelections($b, $this->uid());
        $mappedPackagingMaterialIds = $this->packagingProductSync->materialIds($packagingMaterialSelections);

        // Out Put Type Product eligibility remains limited to Products New Products
        // under the top-level Categories "Rice" and "By Products". The filter
        // controls, however, intentionally show ALL Product Categories and their
        // linked Sub Categories, as requested. The checked Product list remains
        // the authoritative Output mapping.
        $outputTypeMasterData = $this->outputTypeMasterData($b);
        $outputTypeCategoryHierarchy = [
            'categories' => $outputTypeMasterData['categories'],
            'subcategories' => $outputTypeMasterData['subcategories'],
        ];
        $outputTypeEligibleCategoryIds = $outputTypeMasterData['eligible_category_ids'] ?? [];
        $outputTypeProductOptions = $outputTypeMasterData['products'];
        $outputTypeSelections = $this->outputTypeProducts->selections($b);

        // Product Category Mapping can be disabled without deleting it. Existing
        // category/account selections stay stored so the user can edit or re-enable
        // the mapping later. A missing legacy status flag means Enabled.
        $paddyMappingEnabled = $this->productCategoryMappingEnabled($settings, 'paddy');
        $riceMappingEnabled = $this->productCategoryMappingEnabled($settings, 'rice');

        // Every Product used by Rice Mill comes from Products New. Disabled
        // mappings are not exposed to new Paddy Variety / Rice Product selection.
        $paddyProducts = $paddyMappingEnabled
            ? $this->masters->productsByCategory(
                $b,
                (int) ($settings['paddy_product_category_id'] ?? 0)
            )
            : [];
        $riceMasterProducts = $riceMappingEnabled
            ? $this->masters->productsByCategory(
                $b,
                (int) ($settings['rice_product_category_id'] ?? 0)
            )
            : [];

        // Older Rice Mill rows may pre-date the permanent Products New link.
        // Infer their source Product by exact Product Name + SKU so Edit can
        // still open correctly until the idempotent SQL backfill is imported.
        foreach ($products as $product) {
            $sourceId = $this->hasRiceProductLinkColumn()
                ? (int) ($product->products_new_product_id ?? 0)
                : 0;
            if ($sourceId <= 0) {
                foreach ($riceMasterProducts as $candidate) {
                    if (
                        strcasecmp(trim((string)($candidate['name'] ?? '')), trim((string)$product->name)) === 0
                        && strcasecmp(trim((string)($candidate['code'] ?? '')), trim((string)$product->code)) === 0
                    ) {
                        $sourceId = (int) $candidate['id'];
                        break;
                    }
                }
            }
            $product->setAttribute('source_product_id', $sourceId ?: null);
        }

        // Material Usage Mapping is also available directly inside Settings.
        // The same rcm_packaging_material_mappings rows are consumed by
        // PackingService, so packaging automatically reduces mapped materials.
        $materialMappingRows = PackagingMaterialMapping::forBusiness($b)
            ->whereIn('material_id', $mappedPackagingMaterialIds)
            ->with(['product:id,business_id,name','material:id,business_id,name,unit'])
            ->orderBy('product_id')->orderBy('bag_size_kg')->orderBy('material_id')->get();
        // Material Usage Mapping must expose ONLY Products explicitly mapped
        // in Settings > Product Category Mapping > Packaging Material.
        // Do not leak legacy/manual Packaging Material rows into this selector.
        $packagingMaterials = PackagingMaterial::forBusiness($b)
            ->whereIn('id', $mappedPackagingMaterialIds)
            ->orderByDesc('active')->orderBy('name')
            ->get(['id','name','unit','current_qty','active']);

        return view('RiceMill::settings.index', [
            'setting' => $setting,
            'settings' => $settings,
            'varieties' => $varieties,
            'mills' => $mills,
            'locations' => $locations,
            'locationNames' => $locationNames,
            'products' => $products,
            'varietyNames' => $varietyNames,
            'documentPrefix' => $prefix,
            'purchaseSeries' => $purchaseSeries,
            'weighbridgeSeries' => $weighbridgeSeries,
            'receiptSeries' => $receiptSeries,
            'numberingLimits' => [
                'weighbridge' => $this->sequenceLimit($b, 'rcm_weighbridge_entries', 'entry_no', $prefix . '-WB-'),
                'paddy_receipt' => $this->sequenceLimit($b, 'rcm_paddy_receipts', 'receipt_no', $prefix . '-RCV-'),
                'paddy_purchase' => $this->sequenceLimit($b, 'rcm_paddy_purchases', 'purchase_no', $prefix . '-PUR-'),
            ],
            'purchaseHasTransactions' => $purchaseHasTransactions,
            'purchaseOpeningSaved' => $purchaseOpeningSaved,
            'productCategories' => $productCategories,
            'currentLiabilityAccounts' => $currentLiabilityAccounts,
            'paddyMappingEnabled' => $paddyMappingEnabled,
            'riceMappingEnabled' => $riceMappingEnabled,
            'paddyProducts' => $paddyProducts,
            'riceMasterProducts' => $riceMasterProducts,
            'materialMappingRows' => $materialMappingRows,
            'packagingMaterials' => $packagingMaterials,
            'packagingCategoryHierarchy' => $packagingCategoryHierarchy,
            'packagingProductOptions' => $packagingProductOptions,
            'packagingMaterialSelections' => $packagingMaterialSelections,
            'outputTypeCategoryHierarchy' => $outputTypeCategoryHierarchy,
            'outputTypeEligibleCategoryIds' => $outputTypeEligibleCategoryIds,
            'outputTypeProductOptions' => $outputTypeProductOptions,
            'outputTypeSelections' => $outputTypeSelections,
        ]);
    }

    public function saveProductCategoryMapping(Request $request)
    {
        $b = $this->bid();
        $categoryIds = array_map('intval', array_column($this->masters->productCategories($b), 'id'));
        $currentLiabilityIds = array_map('intval', array_column($this->masters->currentLiabilityAccounts($b), 'id'));

        $data = $request->validate([
            'paddy_product_category_id' => ['required','integer',Rule::in($categoryIds)],
            'paddy_payment_account_id' => ['required','integer',Rule::in($currentLiabilityIds)],
            'rice_product_category_id' => ['required','integer',Rule::in($categoryIds)],
            'rice_payment_account_id' => ['required','integer',Rule::in($currentLiabilityIds)],
        ], [
            'paddy_product_category_id.required' => 'Please select the Product Category for Paddy.',
            'paddy_payment_account_id.required' => 'Please select a Current Liabilities Payment Account for Paddy.',
            'rice_product_category_id.required' => 'Please select the Product Category for Rice.',
            'rice_payment_account_id.required' => 'Please select a Current Liabilities Payment Account for Rice.',
        ]);

        $existingRow = Setting::forBusiness($b)->select(['settings'])->first();
        $existing = (array) optional($existingRow)->settings;

        // Saving edits must not silently re-enable a mapping that the user
        // deliberately disabled. Legacy/new mappings without a status flag are
        // Enabled by default.
        $this->mergeSettings([
            'paddy_product_category_id' => (int)$data['paddy_product_category_id'],
            'paddy_payment_account_id' => (int)$data['paddy_payment_account_id'],
            'paddy_product_category_mapping_enabled' => array_key_exists('paddy_product_category_mapping_enabled', $existing)
                ? (bool) $existing['paddy_product_category_mapping_enabled']
                : true,
            'rice_product_category_id' => (int)$data['rice_product_category_id'],
            'rice_payment_account_id' => (int)$data['rice_payment_account_id'],
            'rice_product_category_mapping_enabled' => array_key_exists('rice_product_category_mapping_enabled', $existing)
                ? (bool) $existing['rice_product_category_mapping_enabled']
                : true,
        ]);

        return redirect()->to($this->settingsTabUrl('product-category-mapping'))
            ->with('status','Product Category Mapping saved.');
    }

    public function savePackagingMaterialProductSelection(Request $request)
    {
        $b = $this->bid();
        $hierarchy = $this->masters->productCategoryHierarchy($b);
        $products = $this->masters->productsNewProducts($b);

        $categoryIds = array_map('intval', array_column($hierarchy['categories'] ?? [], 'id'));
        $subCategoryIds = array_map('intval', array_column($hierarchy['subcategories'] ?? [], 'id'));
        $productIds = array_map('intval', array_column($products, 'id'));

        $data = $request->validate([
            'packaging_product_category_id' => ['nullable','integer',Rule::in($categoryIds)],
            'packaging_product_sub_category_id' => ['nullable','integer',Rule::in($subCategoryIds)],
            'packaging_product_ids' => ['required','array','min:1'],
            'packaging_product_ids.*' => ['required','integer','distinct',Rule::in($productIds)],
        ], [
            'packaging_product_ids.required' => 'Please select at least one Product from Products New.',
            'packaging_product_ids.min' => 'Please select at least one Product from Products New.',
            'packaging_product_ids.*.distinct' => 'The same Product cannot be selected more than once.',
        ]);

        $categoryId = (int) ($data['packaging_product_category_id'] ?? 0);
        $subCategoryId = (int) ($data['packaging_product_sub_category_id'] ?? 0);
        $selectedProductIds = array_values(array_unique(array_map('intval', $data['packaging_product_ids'] ?? [])));

        $subRowsById = [];
        foreach ($hierarchy['subcategories'] ?? [] as $sub) {
            $subRowsById[(int) $sub['id']] = $sub;
        }
        $subRow = $subRowsById[$subCategoryId] ?? null;
        if ($categoryId > 0 && $subCategoryId > 0 && (! $subRow || (int) $subRow['parent_id'] !== $categoryId)) {
            throw ValidationException::withMessages([
                'packaging_product_sub_category_id' => 'The selected Product Sub Category is not linked to the selected Product Category.',
            ]);
        }

        $productsById = [];
        foreach ($products as $product) {
            $productsById[(int) $product['id']] = $product;
        }

        foreach ($selectedProductIds as $productId) {
            $product = $productsById[$productId] ?? null;
            if (! $product) {
                throw ValidationException::withMessages([
                    'packaging_product_ids' => 'One or more selected Products New Products are not available.',
                ]);
            }

            if ($categoryId > 0) {
                $productCategoryId = (int) ($product['category_id'] ?? 0);
                $productSubCategoryId = (int) ($product['sub_category_id'] ?? 0);
                $subParentMatches = $productSubCategoryId > 0
                    && isset($subRowsById[$productSubCategoryId])
                    && (int) $subRowsById[$productSubCategoryId]['parent_id'] === $categoryId;

                if ($productCategoryId !== $categoryId && ! $subParentMatches) {
                    throw ValidationException::withMessages([
                        'packaging_product_ids' => 'One or more selected Products are not linked to the selected Product Category.',
                    ]);
                }
            }

            if ($subCategoryId > 0 && (int) ($product['sub_category_id'] ?? 0) !== $subCategoryId) {
                throw ValidationException::withMessages([
                    'packaging_product_ids' => 'One or more selected Products are not linked to the selected Product Sub Category.',
                ]);
            }
        }

        $row = Setting::forBusiness($b)->select(['settings'])->first();
        $settings = array_merge($this->defaults(), (array) optional($row)->settings);
        $selections = array_values(array_filter(
            (array) ($settings['packaging_material_product_selections'] ?? []),
            static fn ($item) => is_array($item) && (int) ($item['product_id'] ?? 0) > 0
        ));

        $selectionIndexByProduct = [];
        foreach ($selections as $index => $selection) {
            $selectionIndexByProduct[(int) ($selection['product_id'] ?? 0)] = $index;
        }

        foreach ($selectedProductIds as $productId) {
            $existingIndex = $selectionIndexByProduct[$productId] ?? null;
            $existingMaterialId = $existingIndex !== null
                ? (int) ($selections[$existingIndex]['material_id'] ?? 0)
                : 0;

            $selection = [
                'product_id' => $productId,
                // Keep the exact optional filters chosen for this bulk save.
                // Zero means the filter was intentionally left blank.
                'category_id' => $categoryId,
                'sub_category_id' => $subCategoryId,
                'material_id' => $existingMaterialId,
            ];

            $material = $this->packagingProductSync->syncSelection($b, $selection, $this->uid());
            if ($material) {
                $selection['material_id'] = (int) $material->id;
            }

            if ($existingIndex === null) {
                $selections[] = $selection;
                $selectionIndexByProduct[$productId] = array_key_last($selections);
            } else {
                $selections[$existingIndex] = $selection;
            }
        }

        $this->mergeSettings(['packaging_material_product_selections' => array_values($selections)]);

        $savedCount = count($selectedProductIds);
        return redirect()->to($this->settingsTabUrl('product-category-mapping'))
            ->with('status', $savedCount . ' Packaging Material Product' . ($savedCount === 1 ? '' : 's') . ' saved and synced to Packaging Materials.');
    }

    public function saveOutputTypeProductSelection(Request $request)
    {
        $b = $this->bid();
        $master = $this->outputTypeMasterData($b);

        $categoryIds = array_map('intval', array_column($master['categories'], 'id'));
        $subCategoryIds = array_map('intval', array_column($master['subcategories'], 'id'));
        $productIds = array_map('intval', array_column($master['products'], 'id'));

        $data = $request->validate([
            'output_type_product_category_ids' => ['nullable','array'],
            'output_type_product_category_ids.*' => ['required','integer','distinct',Rule::in($categoryIds)],
            'output_type_product_sub_category_ids' => ['required','array','min:1'],
            'output_type_product_sub_category_ids.*' => ['required','integer','distinct',Rule::in($subCategoryIds)],
            'output_type_product_ids' => ['nullable','array'],
            'output_type_product_ids.*' => ['required','integer','distinct',Rule::in($productIds)],
        ], [
            'output_type_product_category_ids.*.distinct' => 'The same Product Category cannot be selected more than once.',
            'output_type_product_sub_category_ids.required' => 'Select at least one Product Sub Category.',
            'output_type_product_sub_category_ids.min' => 'Select at least one Product Sub Category.',
            'output_type_product_sub_category_ids.*.distinct' => 'The same Product Sub Category cannot be selected more than once.',
            'output_type_product_ids.*.distinct' => 'The same Output Product cannot be selected more than once.',
            'output_type_product_ids.*.in' => 'One or more selected Output Products are not linked to the Rice or By Products Product Categories.',
        ]);

        // Product Category can be selected more than once. Product Sub Category
        // is compulsory, and every selected Sub Category must belong to one of
        // the selected Categories when a Category filter has been chosen.
        $selectedCategoryIds = array_values(array_unique(array_map(
            'intval',
            (array) ($data['output_type_product_category_ids'] ?? [])
        )));
        $selectedSubCategoryIds = array_values(array_unique(array_map(
            'intval',
            (array) ($data['output_type_product_sub_category_ids'] ?? [])
        )));

        $subRowsById = [];
        foreach ($master['subcategories'] as $subCategory) {
            $subRowsById[(int) ($subCategory['id'] ?? 0)] = $subCategory;
        }
        if ($selectedCategoryIds) {
            foreach ($selectedSubCategoryIds as $subCategoryId) {
                $subCategory = $subRowsById[$subCategoryId] ?? null;
                if (! $subCategory || ! in_array((int) ($subCategory['top_category_id'] ?? 0), $selectedCategoryIds, true)) {
                    throw ValidationException::withMessages([
                        'output_type_product_sub_category_ids' => 'One or more selected Product Sub Categories are not linked to the selected Product Categories.',
                    ]);
                }
            }
        }

        // Persist only checked Products that belong to the compulsory selected
        // Product Sub Categories. This mirrors the browser filter and prevents a
        // hidden/stale Product id from being submitted manually.
        $selectedProductIds = array_values(array_unique(array_map(
            'intval',
            (array) ($data['output_type_product_ids'] ?? [])
        )));

        $productsById = [];
        foreach ($master['products'] as $product) {
            $productsById[(int) $product['id']] = $product;
        }

        $selections = [];
        foreach ($selectedProductIds as $productId) {
            $product = $productsById[$productId] ?? null;
            if (! $product) {
                continue;
            }
            $productSubCategoryId = (int) ($product['sub_category_id'] ?? 0);
            if ($productSubCategoryId <= 0 || ! in_array($productSubCategoryId, $selectedSubCategoryIds, true)) {
                throw ValidationException::withMessages([
                    'output_type_product_ids' => 'Every selected Output Product must belong to one of the selected Product Sub Categories.',
                ]);
            }
            $selections[] = [
                'product_id' => $productId,
                'category_id' => (int) ($product['top_category_id'] ?? $product['category_id'] ?? 0),
                'sub_category_id' => (int) ($product['sub_category_id'] ?? 0),
            ];
        }

        $this->mergeSettings(['output_type_product_selections' => $selections]);

        return redirect()->to($this->settingsTabUrl('product-category-mapping'))
            ->with('status', count($selections) > 0
                ? count($selections) . ' Out Put Type Product' . (count($selections) === 1 ? '' : 's') . ' mapped.'
                : 'Out Put Type Product mapping cleared.');
    }

    /**
     * Product Category Mapping records are configuration and are never deleted.
     * The user may Disable/Enable Paddy or Rice independently while keeping the
     * saved Products New Category and Current Liabilities Account available for
     * Edit and later reactivation.
     */
    public function toggleProductCategoryMapping(string $type)
    {
        abort_unless(in_array($type, ['paddy','rice'], true), 404);

        $b = $this->bid();
        $row = Setting::forBusiness($b)->select(['settings'])->first();
        $settings = array_merge($this->defaults(), (array) optional($row)->settings);
        $categoryKey = $type . '_product_category_id';
        $accountKey = $type . '_payment_account_id';

        if ((int) ($settings[$categoryKey] ?? 0) <= 0 || (int) ($settings[$accountKey] ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'settings' => ucfirst($type) . ' Product Category Mapping has not been added yet.',
            ]);
        }

        $enabled = ! $this->productCategoryMappingEnabled($settings, $type);
        $this->mergeSettings([
            $type . '_product_category_mapping_enabled' => $enabled,
        ]);

        return redirect()->to($this->settingsTabUrl('product-category-mapping'))
            ->with('status', ucfirst($type) . ' Product Category Mapping ' . ($enabled ? 'enabled.' : 'disabled.'));
    }

    /** Existing general save route retained for backwards compatibility. */
    public function save(Request $request)
    {
        $data = $request->validate([
            'receive_paddy_prefix' => 'nullable|string|max:10',
        ]);
        $data['receive_paddy_prefix'] = 'PD';
        $this->mergeSettings($data);
        return redirect()->to($this->settingsTabUrl('receive-paddy'))->with('status', 'Rice Mill general settings saved.');
    }

    public function saveSalesApproval(Request $request)
    {
        $data = $request->validate([
            'auto_notify_sales_invoice_approval' => 'required|in:0,1',
        ]);

        $enabled = (string) $data['auto_notify_sales_invoice_approval'] === '1';
        $this->mergeSettings([
            'auto_notify_sales_invoice_approval' => $enabled,
        ]);

        return redirect()->to($this->settingsTabUrl('sales-approval'))
            ->with('status', $enabled
                ? 'Automatic Sales Invoice approval notifications enabled.'
                : 'Automatic Sales Invoice approval notifications disabled.');
    }

    public function saveReceivePaddy(Request $request)
    {
        $b = $this->bid();
        $setting = Setting::forBusiness($b)->first();
        $stored = (array) optional($setting)->settings;
        $alreadySaved = (bool) ($stored['paddy_purchase_opening_saved'] ?? false)
            || DB::table('rcm_number_series')
                ->where('business_id', $b)
                ->where('series_type', 'paddy_purchase')
                ->exists()
            || DB::table('rcm_paddy_purchases')->where('business_id', $b)->exists();

        // The normal Receive Paddy page is for the one-time opening setup only.
        // After the opening number is saved, all subsequent numbering changes
        // must go through the controlled Edit Numbering dialog.
        if ($alreadySaved) {
            return redirect()->to($this->settingsTabUrl('receive-paddy'))
                ->withErrors([
                    'paddy_purchase_opening_number' => 'Purchase Order Opening No has already been saved and is locked on this page. Use Edit Numbering for a permitted change.',
                ]);
        }

        $data = $request->validate([
            'paddy_purchase_opening_number' => 'required|integer|min:1|max:999999999999',
        ]);

        $newOpening = max(1, (int) $data['paddy_purchase_opening_number']);
        $prefix = 'PD';

        $this->mergeSettings([
            'receive_paddy_prefix' => $prefix,
            'paddy_purchase_opening_number' => $newOpening,
            'paddy_purchase_opening_saved' => true,
        ]);

        // No Purchase can exist at this point because an existing transaction
        // also makes the opening setup locked. Initialise the series exactly at
        // the saved opening number.
        $this->numbers->resetUnused($b, 'paddy_purchase', $prefix . '-PUR-', $newOpening);

        return redirect()->to($this->settingsTabUrl('receive-paddy'))
            ->with('status', 'Purchase Order Opening No saved and locked. Use Edit Numbering for any permitted adjustment before transactions use the number.');
    }

    /**
     * Controlled adjustment of the three Receive Paddy document sequences.
     * Historical document numbers are never changed. A sequence may move back
     * only as far as one number above the highest number already used.
     */
    public function updateReceivePaddyNumbering(Request $request)
    {
        $b = $this->bid();
        $data = $request->validate([
            'weighbridge_next_number' => 'required|integer|min:1|max:999999999999',
            'paddy_receipt_next_number' => 'required|integer|min:1|max:999999999999',
            'paddy_purchase_opening_number_edit' => 'required|integer|min:1|max:999999999999',
            'paddy_purchase_next_number' => 'required|integer|min:1|max:999999999999',
        ]);

        $prefix = 'PD';
        $series = [
            'weighbridge' => [
                'input' => 'weighbridge_next_number',
                'type' => 'weighbridge',
                'prefix' => $prefix . '-WB-',
                'table' => 'rcm_weighbridge_entries',
                'column' => 'entry_no',
                'label' => 'Next Weighbridge No',
            ],
            'paddy_receipt' => [
                'input' => 'paddy_receipt_next_number',
                'type' => 'paddy_receipt',
                'prefix' => $prefix . '-RCV-',
                'table' => 'rcm_paddy_receipts',
                'column' => 'receipt_no',
                'label' => 'Next Paddy Receipt No',
            ],
            'paddy_purchase' => [
                'input' => 'paddy_purchase_next_number',
                'type' => 'paddy_purchase',
                'prefix' => $prefix . '-PUR-',
                'table' => 'rcm_paddy_purchases',
                'column' => 'purchase_no',
                'label' => 'Next Purchase Order No',
            ],
        ];

        // Ensure all three sequence rows exist before the locking transaction.
        // This keeps lock order predictable and avoids duplicate creation races.
        foreach ($series as $cfg) {
            $this->numbers->ensure($b, $cfg['type'], $cfg['prefix'], 1);
        }

        DB::transaction(function () use ($b, $data, $series) {
            // Lock every series row in a fixed order. Number generation uses the
            // same rows with lockForUpdate(), so no document can be issued while
            // the safe minimum is being checked and the new values are applied.
            $types = collect($series)->pluck('type')->sort()->values()->all();
            DB::table('rcm_number_series')
                ->where('business_id', $b)
                ->whereIn('series_type', $types)
                ->orderBy('series_type')
                ->lockForUpdate()
                ->get();

            $errors = [];
            $limits = [];
            foreach ($series as $key => $cfg) {
                $limit = $this->sequenceLimit($b, $cfg['table'], $cfg['column'], $cfg['prefix']);
                $limits[$key] = $limit;
                $requested = (int) $data[$cfg['input']];
                if ($requested < $limit['minimum_next']) {
                    $errors[$cfg['input']] = $cfg['label'] . ' cannot be less than ' . number_format($limit['minimum_next'])
                        . ' because ' . ($limit['highest_used'] > 0
                            ? $this->numbers->format($cfg['prefix'], $limit['highest_used']) . ' is already used.'
                            : 'the sequence must start from 1.');
                }
            }

            $setting = Setting::forBusiness($b)->first();
            $currentSettings = array_merge($this->defaults(), (array) optional($setting)->settings);
            $currentOpening = max(1, (int) ($currentSettings['paddy_purchase_opening_number'] ?? 1));
            $requestedOpening = max(1, (int) $data['paddy_purchase_opening_number_edit']);
            $requestedPurchaseNext = max(1, (int) $data['paddy_purchase_next_number']);
            $purchaseHasTransactions = DB::table('rcm_paddy_purchases')
                ->where('business_id', $b)
                ->exists();

            // Opening No is historical configuration. Once a Paddy Purchase
            // transaction exists it cannot be changed, even through Edit Numbering.
            if ($purchaseHasTransactions && $requestedOpening !== $currentOpening) {
                $errors['paddy_purchase_opening_number_edit'] = 'Purchase Order Opening No cannot be changed because Purchase Order transactions already exist.';
            }

            // Before transactions begin, the next number must not start below
            // the selected opening number.
            if (!$purchaseHasTransactions && $requestedPurchaseNext < $requestedOpening) {
                $errors['paddy_purchase_next_number'] = 'Next Purchase Order No cannot be lower than the Purchase Order Opening No (' . number_format($requestedOpening) . ').';
            }

            if ($errors) {
                throw ValidationException::withMessages($errors);
            }

            foreach ($series as $cfg) {
                $this->numbers->setNextNumber(
                    $b,
                    $cfg['type'],
                    $cfg['prefix'],
                    (int) $data[$cfg['input']]
                );
            }

            $settingChanges = [
                'receive_paddy_prefix' => 'PD',
                'paddy_purchase_opening_saved' => true,
            ];

            // Only before the first Purchase Order transaction may Edit Numbering
            // change the saved opening number. Once used, it remains immutable.
            if (!$purchaseHasTransactions) {
                $settingChanges['paddy_purchase_opening_number'] = $requestedOpening;
            }

            $this->mergeSettings($settingChanges);
        });

        return redirect()->to($this->settingsTabUrl('receive-paddy'))
            ->with('status', 'Receive Paddy next numbers updated safely. Historical numbers were not changed.');
    }

    /** Backward-compatible create endpoint. */
    public function variety(Request $request)
    {
        return $this->storeVariety($request);
    }

    public function storeVariety(Request $request)
    {
        $b = $this->bid();
        $data = $this->validateVariety($request);
        $opening = max(1, (int) $data['lot_opening_number']);

        $variety = PaddyVariety::create(array_merge($data, [
            'business_id' => $b,
            'code' => strtoupper(trim($data['code'])),
            'lot_opening_number' => $opening,
            'active' => 1,
            'created_by' => $this->uid(),
        ]));

        $prefix = $this->currentPrefix($b);
        $series = $this->numbers->resetUnused(
            $b,
            $this->numbers->varietyLotType((int) $variety->id),
            $this->numbers->varietyLotPrefix($prefix, (string) $variety->code),
            $opening
        );

        $this->decorateVarietyForSettings($variety, $series, false, false);

        if ($request->expectsJson()) {
            return response()->json($this->varietyAjaxPayload(
                $variety,
                'Paddy variety and Receive Paddy settings added.',
                'create'
            ));
        }

        return redirect()->to($this->settingsTabUrl('paddy-variety'))->with('status', 'Paddy variety and Receive Paddy settings added.');
    }

    public function updateVariety(Request $request, int $id)
    {
        $b = $this->bid();
        $variety = PaddyVariety::forBusiness($b)->findOrFail($id);
        $data = $this->validateVariety($request, $id);
        $data['code'] = strtoupper(trim($data['code']));
        $newOpening = max(1, (int) $data['lot_opening_number']);
        $sequenceUsed = $this->varietyLotSequenceUsed($b, $id, max(1, (int) ($variety->lot_opening_number ?: 1)));

        if ($sequenceUsed && $newOpening !== max(1, (int) ($variety->lot_opening_number ?: 1))) {
            throw ValidationException::withMessages([
                'lot_opening_number' => 'The Stock Lot opening number cannot be changed after this Paddy Variety has been used in a transaction.',
            ]);
        }

        $variety->update($data);
        $prefix = $this->currentPrefix($b);
        $seriesType = $this->numbers->varietyLotType($id);
        $seriesPrefix = $this->numbers->varietyLotPrefix($prefix, (string) $variety->code);

        $series = $sequenceUsed
            ? $this->numbers->ensure($b, $seriesType, $seriesPrefix, $newOpening)
            : $this->numbers->resetUnused($b, $seriesType, $seriesPrefix, $newOpening);

        // The existing record already tells us whether the sequence has been
        // used. Avoid reloading the whole Settings page just to rediscover it.
        $hasTransactions = $sequenceUsed || $this->varietyHasTransactions($b, $id);
        $this->decorateVarietyForSettings($variety, $series, $sequenceUsed, $hasTransactions);

        if ($request->expectsJson()) {
            return response()->json($this->varietyAjaxPayload(
                $variety,
                'Paddy variety settings updated.',
                'update'
            ));
        }

        return redirect()->to($this->settingsTabUrl('paddy-variety'))->with('status', 'Paddy variety settings updated.');
    }

    public function deleteVariety(int $id)
    {
        $b = $this->bid();
        $variety = PaddyVariety::forBusiness($b)->findOrFail($id);
        if ($this->varietyHasTransactions($b, $id)) {
            return redirect()->to($this->settingsTabUrl('paddy-variety'))->withErrors(['settings' => 'This Paddy Variety cannot be deleted because it is already used in Rice Mill transactions. You can Disable it instead.']);
        }
        $variety->delete();
        $this->numbers->delete($b, $this->numbers->varietyLotType($id));
        return redirect()->to($this->settingsTabUrl('paddy-variety'))->with('status', 'Paddy variety deleted.');
    }

    public function toggleVariety(int $id)
    {
        $row = PaddyVariety::forBusiness($this->bid())->findOrFail($id);
        $row->update(['active' => !$row->active]);
        return redirect()->to($this->settingsTabUrl('paddy-variety'))->with('status', 'Paddy variety ' . ($row->fresh()->active ? 'enabled.' : 'disabled.'));
    }

    /** Backward-compatible create endpoint. */
    public function mill(Request $request)
    {
        return $this->storeMill($request);
    }

    public function storeMill(Request $request)
    {
        $b = $this->bid();
        $data = $this->validateMill($request);
        $row = Mill::create(array_merge($data, ['business_id'=>$b,'active'=>1,'created_by'=>$this->uid()]));
        $row->setAttribute('has_transactions', false);

        if ($request->expectsJson()) {
            return response()->json($this->millAjaxPayload($row, 'Mill added.', 'create'));
        }
        return redirect()->to($this->settingsTabUrl('mills'))->with('status','Mill added.');
    }

    public function updateMill(Request $request, int $id)
    {
        $b = $this->bid();
        $row = Mill::forBusiness($b)->findOrFail($id);
        if ($row->location_id) {
            $this->locationAccess->assertBusinessLocation((int) $row->location_id, $b);
        }
        $row->update($this->validateMill($request));
        $row->setAttribute('has_transactions', DB::table('rcm_production_batches')->where('business_id',$b)->where('mill_id',$id)->exists());

        if ($request->expectsJson()) {
            return response()->json($this->millAjaxPayload($row, 'Mill updated.', 'update'));
        }
        return redirect()->to($this->settingsTabUrl('mills'))->with('status','Mill updated.');
    }

    public function deleteMill(int $id)
    {
        $b = $this->bid();
        $row = Mill::forBusiness($b)->findOrFail($id);
        if ($row->location_id) {
            $this->locationAccess->assertBusinessLocation((int) $row->location_id, $b);
        }
        if (DB::table('rcm_production_batches')->where('business_id',$b)->where('mill_id',$id)->exists()) {
            return redirect()->to($this->settingsTabUrl('mills'))->withErrors(['settings'=>'This Mill cannot be deleted because it is already used in Production transactions. You can Disable it instead.']);
        }
        $row->delete();
        return redirect()->to($this->settingsTabUrl('mills'))->with('status','Mill deleted.');
    }

    public function toggleMill(int $id)
    {
        $b = $this->bid();
        $row = Mill::forBusiness($b)->findOrFail($id);
        if ($row->location_id) {
            $this->locationAccess->assertBusinessLocation((int) $row->location_id, $b);
        }
        $row->update(['active'=>!$row->active]);
        return redirect()->to($this->settingsTabUrl('mills'))->with('status','Mill '.($row->fresh()->active?'enabled.':'disabled.'));
    }

    /** Backward-compatible create endpoint. */
    public function product(Request $request)
    {
        return $this->storeProduct($request);
    }

    public function storeProduct(Request $request)
    {
        $b = $this->bid();
        $data = $this->validateProduct($request);
        $row = RiceProduct::create(array_merge($data,['business_id'=>$b,'current_qty'=>0,'active'=>1,'created_by'=>$this->uid()]));
        $row->setAttribute('has_transactions', false);

        if ($request->expectsJson()) {
            return response()->json($this->productAjaxPayload($row, 'Rice product added.', 'create'));
        }
        return redirect()->to($this->settingsTabUrl('rice-products'))->with('status','Rice product added.');
    }

    public function updateProduct(Request $request, int $id)
    {
        $b = $this->bid();
        $row = RiceProduct::forBusiness($b)->findOrFail($id);
        $row->update($this->validateProduct($request, $id));
        $row->setAttribute('has_transactions', $this->productHasTransactions($b, $id));

        if ($request->expectsJson()) {
            return response()->json($this->productAjaxPayload($row, 'Rice product updated.', 'update'));
        }
        return redirect()->to($this->settingsTabUrl('rice-products'))->with('status','Rice product updated.');
    }

    public function deleteProduct(int $id)
    {
        $b = $this->bid();
        $row = RiceProduct::forBusiness($b)->findOrFail($id);
        if ($this->productHasTransactions($b, $id)) {
            return redirect()->to($this->settingsTabUrl('rice-products'))->withErrors(['settings'=>'This Rice Product cannot be deleted because it is already used in Rice Mill transactions. You can Disable it instead.']);
        }
        $row->delete();
        return redirect()->to($this->settingsTabUrl('rice-products'))->with('status','Rice product deleted.');
    }

    public function toggleProduct(int $id)
    {
        $row = RiceProduct::forBusiness($this->bid())->findOrFail($id);
        $row->update(['active'=>!$row->active]);
        return redirect()->to($this->settingsTabUrl('rice-products'))->with('status','Rice product '.($row->fresh()->active?'enabled.':'disabled.'));
    }


    /**
     * Populate the same read-only attributes used by the Settings table without
     * executing the full Settings index query set. This is used by the instant
     * Add/Edit Paddy Variety AJAX response.
     */
    private function decorateVarietyForSettings(
        PaddyVariety $variety,
        object $series,
        bool $sequenceUsed,
        bool $hasTransactions
    ): void {
        $prefix = (string) ($series->prefix ?? $this->numbers->varietyLotPrefix(
            'PD',
            (string) $variety->code
        ));
        $next = max(1, (int) ($series->next_number ?? $variety->lot_opening_number ?? 1));

        $variety->setAttribute('lot_sequence_prefix', $prefix);
        $variety->setAttribute('lot_next_number', $next);
        $variety->setAttribute('lot_next_preview', $this->numbers->format($prefix, $next));
        $variety->setAttribute('lot_sequence_used', $sequenceUsed);
        $variety->setAttribute('has_transactions', $hasTransactions);
    }

    /**
     * Lightweight response for Settings Paddy Variety Add/Edit. The browser
     * replaces only the affected row + its View/Edit modals, so unrelated
     * Settings queries and tables are not reloaded after every save.
     */
    private function varietyAjaxPayload(PaddyVariety $variety, string $message, string $mode): array
    {
        $paddyProducts = $this->paddyProductsForSettings($this->bid());

        return [
            'ok' => true,
            'mode' => $mode,
            'id' => (int) $variety->id,
            'message' => $message,
            'row_html' => view('RiceMill::settings.partials.variety-row', [
                'v' => $variety,
            ])->render(),
            'modals_html' => view('RiceMill::settings.partials.variety-modals', [
                'v' => $variety,
                'paddyProducts' => $paddyProducts,
            ])->render(),
        ];
    }

    /** Lightweight Add/Edit Mill response: replace only one table row + modals. */
    private function millAjaxPayload(Mill $mill, string $message, string $mode): array
    {
        $locations = $this->locationAccess->businessOptions($this->bid());
        $locationNames = [];
        foreach ($locations as $location) {
            $locationNames[(int) $location['id']] = (string) $location['name'];
        }

        return [
            'ok' => true,
            'kind' => 'mill',
            'mode' => $mode,
            'id' => (int) $mill->id,
            'message' => $message,
            'row_html' => view('RiceMill::settings.partials.mill-row', ['m'=>$mill,'locationNames'=>$locationNames])->render(),
            'modals_html' => view('RiceMill::settings.partials.mill-modals', [
                'm' => $mill,
                'locations' => $locations,
                'locationNames' => $locationNames,
            ])->render(),
        ];
    }

    /** Lightweight Add/Edit Rice Product response. */
    private function productAjaxPayload(RiceProduct $product, string $message, string $mode): array
    {
        $businessId = $this->bid();
        $varieties = PaddyVariety::forBusiness($businessId)
            ->orderBy('name')
            ->get(['id','code','name']);
        $varietyNames = $varieties->mapWithKeys(fn($v) => [(int) $v->id => trim(($v->code ? $v->code . ' - ' : '') . $v->name)])->all();
        $riceMasterProducts = $this->riceProductsForSettings($businessId);

        $sourceId = $this->hasRiceProductLinkColumn()
            ? (int) ($product->products_new_product_id ?? 0)
            : 0;
        if ($sourceId <= 0) {
            foreach ($riceMasterProducts as $candidate) {
                if (
                    strcasecmp(trim((string)($candidate['name'] ?? '')), trim((string)$product->name)) === 0
                    && strcasecmp(trim((string)($candidate['code'] ?? '')), trim((string)$product->code)) === 0
                ) {
                    $sourceId = (int) $candidate['id'];
                    break;
                }
            }
        }
        $product->setAttribute('source_product_id', $sourceId ?: null);

        return [
            'ok' => true,
            'kind' => 'product',
            'mode' => $mode,
            'id' => (int) $product->id,
            'message' => $message,
            'row_html' => view('RiceMill::settings.partials.product-row', ['p'=>$product,'varietyNames'=>$varietyNames])->render(),
            'modals_html' => view('RiceMill::settings.partials.product-modals', [
                'p'=>$product,
                'varieties'=>$varieties,
                'varietyNames'=>$varietyNames,
                'riceMasterProducts'=>$riceMasterProducts,
            ])->render(),
        ];
    }

    /**
     * Some existing tenant databases may receive the module code before the
     * optional Product-link SQL is imported. Keep Paddy Variety Settings usable
     * in that state; name/code remain server-derived from Products New either way.
     */
    private function hasPaddyProductLinkColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasTable('rcm_paddy_varieties')
                && Schema::hasColumn('rcm_paddy_varieties', 'paddy_product_id');
        }

        return $hasColumn;
    }

    /**
     * rcm_products remains Rice Mill's operational stock/cache table, but its
     * Product master identity comes only from Products New.
     */
    private function hasRiceProductLinkColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasTable('rcm_products')
                && Schema::hasColumn('rcm_products', 'products_new_product_id');
        }

        return $hasColumn;
    }

    private function validateVariety(Request $request, ?int $ignoreId = null): array
    {
        $b = $this->bid();
        $paddyProducts = $this->paddyProductsForSettings($b);
        $productIds = array_map('intval', array_column($paddyProducts, 'id'));

        $request->validate([
            'paddy_product_id' => ['required','integer',Rule::in($productIds)],
        ], [
            'paddy_product_id.required' => 'Please select the Paddy Variety Name from the mapped Paddy category in Products New.',
            'paddy_product_id.in' => 'The selected Paddy Variety Name is not an active Products New Product in the mapped Paddy category.',
        ]);

        $selectedProductId = (int) $request->input('paddy_product_id');
        $product = null;
        foreach ($paddyProducts as $candidate) {
            if ((int) ($candidate['id'] ?? 0) === $selectedProductId) {
                $product = $candidate;
                break;
            }
        }

        if (! $product) {
            throw ValidationException::withMessages([
                'paddy_product_id' => 'The selected Paddy Product is no longer available in the mapped Products New Paddy category.',
            ]);
        }

        // Name and Code always come from the Products New master. The browser
        // only displays the code; server-side derivation prevents changing it by
        // editing the request payload.
        $name = trim((string) ($product['name'] ?? ''));
        $code = preg_replace('/\s+/u', ' ', trim((string) ($product['code'] ?? '')));

        if ($code === '') {
            throw ValidationException::withMessages([
                'paddy_product_id' => 'The selected Paddy Product does not have a SKU. Please add the SKU in Products New first.',
            ]);
        }

        $request->merge([
            'name' => $name,
            'code' => $code,
            'paddy_product_id' => $selectedProductId,
        ]);

        $rules = [
            'code' => [
                'required','string','max:20','regex:/^[\pL\pN][\pL\pN _.\-\/]*$/u',
                Rule::unique('rcm_paddy_varieties','code')->where(fn($q)=>$q->where('business_id',$b))->ignore($ignoreId),
            ],
            'name' => [
                'required','string','max:120',
                Rule::unique('rcm_paddy_varieties','name')->where(fn($q)=>$q->where('business_id',$b))->ignore($ignoreId),
            ],
            'default_moisture_percent' => 'nullable|numeric|min:0|max:100',
            'foreign_matter_limit_percent' => 'nullable|numeric|min:0|max:100',
            'expected_rice_yield_percent' => 'nullable|numeric|min:0|max:100',
            'expected_broken_rice_percent' => 'nullable|numeric|min:0|max:100',
            'expected_bran_percent' => 'nullable|numeric|min:0|max:100',
            'expected_husk_percent' => 'nullable|numeric|min:0|max:100',
            'expected_process_loss_percent' => 'nullable|numeric|min:0|max:100',
            'quality_grade' => 'nullable|string|max:50',
            'lot_opening_number' => 'required|integer|min:1|max:999999999999',
        ];

        if ($this->hasPaddyProductLinkColumn()) {
            $rules['paddy_product_id'] = [
                'required','integer',Rule::in($productIds),
                Rule::unique('rcm_paddy_varieties','paddy_product_id')
                    ->where(fn($q)=>$q->where('business_id',$b))
                    ->ignore($ignoreId),
            ];
        }

        $data = $request->validate($rules, [
            'paddy_product_id.unique' => 'This Paddy Product is already configured as a Paddy Variety for this business.',
            'code.max' => 'The Products New SKU for this Paddy Product is longer than 20 characters. Please shorten the SKU in Products New before using it as a Paddy Variety.',
            'code.regex' => 'The Product Code / SKU may contain letters, numbers, spaces, hyphens, underscores, dots and slashes only.',
            'code.unique' => 'This Paddy Variety Code is already in use for this business.',
        ]);

        // Do not try to write a column that an older tenant schema does not yet
        // have. The selected Product remains authoritative because name + code
        // were derived above on the server. After the SQL patch is imported,
        // subsequent saves also persist the permanent Product ID link.
        if (! $this->hasPaddyProductLinkColumn()) {
            unset($data['paddy_product_id']);
        }

        return $data;
    }

    /** @return array<int,array{id:int,name:string,code:string}> */
    private function paddyProductsForSettings(int $businessId): array
    {
        $row = Setting::forBusiness($businessId)->select(['settings'])->first();
        $settings = array_merge($this->defaults(), (array) optional($row)->settings);
        $categoryId = (int) ($settings['paddy_product_category_id'] ?? 0);

        return $categoryId > 0 && $this->productCategoryMappingEnabled($settings, 'paddy')
            ? $this->masters->productsByCategory($businessId, $categoryId)
            : [];
    }

    private function validateMill(Request $request): array
    {
        // Use a Mill-specific request key so the host's global operational
        // UserLocationAccess middleware does not mistake a Settings assignment
        // for the user's current working location. The real DB column remains
        // location_id after validation below.
        $data = $request->validate([
            'mill_location_id'=>'required|integer',
            'code'=>'nullable|string|max:30',
            'name'=>'required|string|max:120',
            'capacity_per_hour'=>'nullable|numeric|min:0',
        ]);

        $locationId = (int) $data['mill_location_id'];
        unset($data['mill_location_id']);

        // Active tenant DB + exact business_id is the Settings boundary.
        $this->locationAccess->assertBusinessLocation($locationId, $this->bid(), true);
        $data['location_id'] = $locationId;

        return $data;
    }

    private function validateProduct(Request $request, ?int $ignoreId = null): array
    {
        $b = $this->bid();
        $riceProducts = $this->riceProductsForSettings($b);
        $productIds = array_map('intval', array_column($riceProducts, 'id'));

        $request->validate([
            'products_new_product_id' => ['required','integer',Rule::in($productIds)],
        ], [
            'products_new_product_id.required' => 'Please select the Rice Product from the mapped Products New Rice category.',
            'products_new_product_id.in' => 'The selected Rice Product is not an active Products New Product in the mapped Rice category.',
        ]);

        $selectedProductId = (int) $request->input('products_new_product_id');
        $sourceProduct = null;
        foreach ($riceProducts as $candidate) {
            if ((int) ($candidate['id'] ?? 0) === $selectedProductId) {
                $sourceProduct = $candidate;
                break;
            }
        }

        if (! $sourceProduct) {
            throw ValidationException::withMessages([
                'products_new_product_id' => 'The selected Rice Product is no longer available in Products New.',
            ]);
        }

        $name = trim((string) ($sourceProduct['name'] ?? ''));
        $code = preg_replace('/\s+/u', ' ', trim((string) ($sourceProduct['code'] ?? '')));
        if ($code === '') {
            throw ValidationException::withMessages([
                'products_new_product_id' => 'The selected Products New Rice Product does not have a SKU. Please add the SKU in Products New first.',
            ]);
        }

        // Browser values are never authoritative for Product Name / Code.
        $request->merge([
            'products_new_product_id' => $selectedProductId,
            'name' => $name,
            'code' => $code,
        ]);

        $rules = [
            'code' => 'required|string|max:40',
            'name' => [
                'required','string','max:150',
                Rule::unique('rcm_products','name')->where(fn($q)=>$q->where('business_id',$b))->ignore($ignoreId),
            ],
            'rice_type'=>'nullable|string|max:100',
            'paddy_variety_id'=>[
                'nullable','integer',
                Rule::exists('rcm_paddy_varieties','id')->where(fn($q)=>$q->where('business_id',$b)),
            ],
        ];

        if ($this->hasRiceProductLinkColumn()) {
            $rules['products_new_product_id'] = [
                'required','integer',Rule::in($productIds),
                Rule::unique('rcm_products','products_new_product_id')
                    ->where(fn($q)=>$q->where('business_id',$b))
                    ->ignore($ignoreId),
            ];
        }

        $data = $request->validate($rules, [
            'products_new_product_id.unique' => 'This Products New Rice Product is already linked in Rice Mill.',
        ]);

        // Code-first deployment compatibility: when the tenant SQL has not yet
        // been imported, still source Name/SKU from Products New but do not try
        // to write the optional link column.
        if (! $this->hasRiceProductLinkColumn()) {
            unset($data['products_new_product_id']);
        }

        return $data;
    }

    /** @return array<int,array{id:int,name:string,code:string}> */
    private function riceProductsForSettings(int $businessId): array
    {
        $row = Setting::forBusiness($businessId)->select(['settings'])->first();
        $settings = array_merge($this->defaults(), (array) optional($row)->settings);
        $categoryId = (int) ($settings['rice_product_category_id'] ?? 0);

        return $categoryId > 0 && $this->productCategoryMappingEnabled($settings, 'rice')
            ? $this->masters->productsByCategory($businessId, $categoryId)
            : [];
    }

    /**
     * Legacy mappings pre-date the explicit status flag, so a fully configured
     * mapping is treated as Enabled until the user disables it.
     */
    private function productCategoryMappingEnabled(array $settings, string $type): bool
    {
        $categoryId = (int) ($settings[$type . '_product_category_id'] ?? 0);
        $accountId = (int) ($settings[$type . '_payment_account_id'] ?? 0);
        if ($categoryId <= 0 || $accountId <= 0) {
            return false;
        }

        $key = $type . '_product_category_mapping_enabled';
        return array_key_exists($key, $settings) ? (bool) $settings[$key] : true;
    }

    private function mergeSettings(array $changes): Setting
    {
        $b = $this->bid();
        $row = Setting::forBusiness($b)->first();
        $merged = array_merge($this->defaults(), (array) optional($row)->settings, $changes);
        return Setting::updateOrCreate(['business_id'=>$b],['settings'=>$merged,'updated_by'=>$this->uid()]);
    }

    /**
     * Keep Settings actions on the tab where the user worked. A fragment is
     * returned in the Location header so the browser re-opens that tab after
     * the POST/PUT/DELETE redirect instead of falling back to the first tab.
     */
    private function settingsTabUrl(string $tab): string
    {
        return route('rice-mill.settings.index') . '#rcm-settings-' . $tab;
    }

    /**
     * Build a read-only sequence preview from the Settings page's single
     * number-series snapshot. Writes still go through NumberSeriesService.
     */
    private function seriesSnapshot(?object $row, string $fallbackPrefix, int $openingNumber = 1, int $pad = 6): array
    {
        $next = $row ? max(1, (int) $row->next_number) : max(1, $openingNumber);
        $prefix = $row && $row->prefix !== null ? (string) $row->prefix : $fallbackPrefix;

        return [
            'prefix' => $prefix,
            'next_number' => $next,
            'preview' => $this->numbers->format($prefix, $next, $pad),
        ];
    }

    private function defaults(): array
    {
        return [
            'receive_paddy_prefix' => 'PD',
            'paddy_purchase_opening_number' => 1,
            'auto_notify_sales_invoice_approval' => false,
            'packaging_material_product_selections' => [],
            'output_type_product_selections' => [],
        ];
    }

    private function prefix(array $settings): string
    {
        return 'PD';
    }

    private function currentPrefix(int $businessId): string
    {
        return 'PD';
    }

    private function purchaseSequenceUsed(int $businessId, int $openingNumber): bool
    {
        $row = DB::table('rcm_number_series')
            ->where('business_id',$businessId)
            ->where('series_type','paddy_purchase')
            ->first();
        if (!$row || !str_starts_with((string) $row->prefix, 'PD-PUR-')) {
            return false;
        }
        return (int) $row->next_number > max(1, $openingNumber);
    }

    private function varietyLotSequenceUsed(int $businessId, int $varietyId, int $openingNumber): bool
    {
        $row = DB::table('rcm_number_series')
            ->where('business_id',$businessId)
            ->where('series_type',$this->numbers->varietyLotType($varietyId))
            ->first();
        if (!$row) {
            return false;
        }
        return (int) $row->next_number > max(1, $openingNumber);
    }

    /**
     * Return the highest numeric suffix already used for a document prefix and
     * the lowest next number that can be safely selected without duplication.
     */
    private function sequenceLimit(int $businessId, string $table, string $column, string $prefix): array
    {
        $start = strlen($prefix) + 1; // MySQL SUBSTRING is 1-based.
        $maxUsed = DB::table($table)
            ->where('business_id', $businessId)
            ->where($column, 'like', $prefix . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(`' . $column . '`, ' . $start . ') AS UNSIGNED)) AS max_number')
            ->value('max_number');

        $highestUsed = max(0, (int) ($maxUsed ?: 0));

        return [
            'highest_used' => $highestUsed,
            'minimum_next' => max(1, $highestUsed + 1),
            'prefix' => $prefix,
        ];
    }

    private function usedVarietyIds(int $businessId): array
    {
        $query = DB::table('rcm_paddy_purchase_lines')
            ->where('business_id', $businessId)
            ->whereNotNull('paddy_variety_id')
            ->selectRaw('paddy_variety_id AS id')
            ->union(
                DB::table('rcm_paddy_receipts')
                    ->where('business_id', $businessId)
                    ->whereNotNull('paddy_variety_id')
                    ->selectRaw('paddy_variety_id AS id')
            )
            ->union(
                DB::table('rcm_paddy_lots')
                    ->where('business_id', $businessId)
                    ->whereNotNull('paddy_variety_id')
                    ->selectRaw('paddy_variety_id AS id')
            );

        return $query->get()->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function varietyHasTransactions(int $businessId, int $id): bool
    {
        return DB::table('rcm_paddy_purchase_lines')->where('business_id',$businessId)->where('paddy_variety_id',$id)->exists()
            || DB::table('rcm_paddy_receipts')->where('business_id',$businessId)->where('paddy_variety_id',$id)->exists()
            || DB::table('rcm_paddy_lots')->where('business_id',$businessId)->where('paddy_variety_id',$id)->exists();
    }

    private function usedProductIds(int $businessId): array
    {
        $query = DB::table('rcm_production_outputs')
            ->where('business_id', $businessId)
            ->whereNotNull('product_id')
            ->selectRaw('product_id AS id')
            ->union(
                DB::table('rcm_packing_lines')
                    ->where('business_id', $businessId)
                    ->whereNotNull('product_id')
                    ->selectRaw('product_id AS id')
            )
            ->union(
                DB::table('rcm_dispatch_lines')
                    ->where('business_id', $businessId)
                    ->whereNotNull('product_id')
                    ->selectRaw('product_id AS id')
            )
            ->union(
                DB::table('rcm_finished_stock_movements')
                    ->where('business_id', $businessId)
                    ->whereNotNull('product_id')
                    ->selectRaw('product_id AS id')
            );

        return $query->get()->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function productHasTransactions(int $businessId, int $id): bool
    {
        return DB::table('rcm_production_outputs')->where('business_id',$businessId)->where('product_id',$id)->exists()
            || DB::table('rcm_packing_lines')->where('business_id',$businessId)->where('product_id',$id)->exists()
            || DB::table('rcm_dispatch_lines')->where('business_id',$businessId)->where('product_id',$id)->exists()
            || DB::table('rcm_finished_stock_movements')->where('business_id',$businessId)->where('product_id',$id)->exists();
    }
    /**
     * Build the Products New master list allowed for Rice Mill production outputs.
     * Only the exact top-level Categories "Rice" and "By Products" are eligible,
     * including Products linked through any descendant Sub Category.
     *
     * @return array{categories:array,subcategories:array,products:array}
     */
    private function outputTypeMasterData(int $businessId): array
    {
        $hierarchy = $this->masters->productCategoryHierarchy($businessId);
        $allCategories = array_values($hierarchy['categories'] ?? []);
        $allSubCategories = array_values($hierarchy['subcategories'] ?? []);

        // The filters must show all top-level Product Categories. Keep their
        // natural Products New alphabetical order rather than restricting the
        // Category dropdown to Rice / By Products.
        usort($allCategories, static fn ($a, $b) => strnatcasecmp(
            (string) ($a['name'] ?? ''),
            (string) ($b['name'] ?? '')
        ));

        $categoryNameById = [];
        foreach ($allCategories as $category) {
            $categoryNameById[(int) ($category['id'] ?? 0)] = (string) ($category['name'] ?? '');
        }

        // Attach the top-level Category to every Sub Category so the browser can
        // instantly show only Sub Categories linked to the selected Category.
        $subcategories = [];
        foreach ($allSubCategories as $subcategory) {
            $subcategoryId = (int) ($subcategory['id'] ?? 0);
            if ($subcategoryId <= 0) {
                continue;
            }
            $topId = $this->masters->topLevelProductCategoryId($businessId, $subcategoryId);
            $subcategory['top_category_id'] = $topId;
            $subcategory['top_category_name'] = (string) ($categoryNameById[$topId] ?? '');
            $subcategories[] = $subcategory;
        }
        usort($subcategories, static function ($a, $b) {
            $categoryCompare = strnatcasecmp(
                (string) ($a['top_category_name'] ?? ''),
                (string) ($b['top_category_name'] ?? '')
            );
            return $categoryCompare !== 0
                ? $categoryCompare
                : strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        // Product eligibility itself intentionally remains limited to the exact
        // top-level Categories Rice and By Products.
        $wantedOrder = ['rice' => 0, 'by products' => 1];
        $eligibleCategories = array_values(array_filter(
            $allCategories,
            static fn ($category) => array_key_exists(
                strtolower(trim((string) ($category['name'] ?? ''))),
                $wantedOrder
            )
        ));
        usort($eligibleCategories, static function ($a, $b) use ($wantedOrder) {
            $ak = strtolower(trim((string) ($a['name'] ?? '')));
            $bk = strtolower(trim((string) ($b['name'] ?? '')));
            return ($wantedOrder[$ak] ?? 99) <=> ($wantedOrder[$bk] ?? 99);
        });

        $eligibleCategoryIds = array_map('intval', array_column($eligibleCategories, 'id'));
        $productsById = [];
        foreach ($eligibleCategoryIds as $categoryId) {
            foreach ($this->masters->productsNewProductsForCategory($businessId, $categoryId) as $product) {
                $productId = (int) ($product['id'] ?? 0);
                if ($productId <= 0) {
                    continue;
                }
                $product['top_category_id'] = $categoryId;
                $product['top_category_name'] = (string) ($categoryNameById[$categoryId] ?? '');
                $productsById[$productId] = $product;
            }
        }
        $products = array_values($productsById);
        usort($products, static function ($a, $b) use ($wantedOrder) {
            $ac = strtolower(trim((string) ($a['top_category_name'] ?? '')));
            $bc = strtolower(trim((string) ($b['top_category_name'] ?? '')));
            $categoryCompare = ($wantedOrder[$ac] ?? 99) <=> ($wantedOrder[$bc] ?? 99);
            if ($categoryCompare !== 0) {
                return $categoryCompare;
            }
            return strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return [
            'categories' => $allCategories,
            'subcategories' => $subcategories,
            'eligible_category_ids' => $eligibleCategoryIds,
            'products' => $products,
        ];
    }

}
