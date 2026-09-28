<section id="product-category-mapping-settings" class="rcm-card rcm-settings-section">
    <div class="rcm-settings-section-head">
        <div>
            <div class="rcm-section-title">Product Category Mapping</div>
            <p class="rcm-muted">Products New mappings used by Rice Mill. All sections are collapsed by default; click a coloured section button to expand or collapse it.</p>
        </div>
    </div>

    @if(empty($productCategories))
        <div class="rcm-alert rcm-alert-danger">
            <i class="fa fa-exclamation-circle"></i>
            <span>No Product categories were found in Products New for this business.</span>
        </div>
    @endif
    @if(count($currentLiabilityAccounts ?? []) === 0)
        <div class="rcm-alert rcm-alert-danger">
            <i class="fa fa-exclamation-circle"></i>
            <span>No List Accounts are available under Current Liabilities for this business.</span>
        </div>
    @endif

    @php
        $categoryNames = collect($productCategories)->pluck('name','id');
        $currentLiabilityNames = collect($currentLiabilityAccounts)->pluck('name','id');
        $paddyCategoryId = (int) old('paddy_product_category_id', $settings['paddy_product_category_id'] ?? 0);
        $paddyAccountId = (int) old('paddy_payment_account_id', $settings['paddy_payment_account_id'] ?? 0);
        $riceCategoryId = (int) old('rice_product_category_id', $settings['rice_product_category_id'] ?? 0);
        $riceAccountId = (int) old('rice_payment_account_id', $settings['rice_payment_account_id'] ?? 0);
        $paddyConfigured = $paddyCategoryId > 0 && $paddyAccountId > 0;
        $riceConfigured = $riceCategoryId > 0 && $riceAccountId > 0;
        $paddyEnabled = $paddyMappingEnabled ?? ($paddyConfigured ? true : false);
        $riceEnabled = $riceMappingEnabled ?? ($riceConfigured ? true : false);

        $packagingCategories = $packagingCategoryHierarchy['categories'] ?? [];
        $packagingSubCategories = $packagingCategoryHierarchy['subcategories'] ?? [];
        $packagingCategoryNames = collect($packagingCategories)->pluck('name','id');
        $packagingSubCategoryNames = collect($packagingSubCategories)->pluck('name','id');
        $packagingProductById = collect($packagingProductOptions ?? [])->keyBy('id');
        $selectedPackagingCategoryId = (int) old('packaging_product_category_id', 0);
        $selectedPackagingSubCategoryId = (int) old('packaging_product_sub_category_id', 0);
        $selectedPackagingProductIds = array_values(array_filter(array_map('intval', (array) old('packaging_product_ids', [])), static fn ($id) => $id > 0));

        $outputTypeCategories = $outputTypeCategoryHierarchy['categories'] ?? [];
        $outputTypeSubCategories = $outputTypeCategoryHierarchy['subcategories'] ?? [];
        $outputTypeCategoryNames = collect($outputTypeCategories)->pluck('name','id');
        $outputTypeSubCategoryNames = collect($outputTypeSubCategories)->pluck('name','id');
        $outputTypeProductById = collect($outputTypeProductOptions ?? [])->keyBy('id');
        $savedOutputTypeProductIds = collect($outputTypeSelections ?? [])->pluck('product_id')->map(fn ($id) => (int) $id)->filter()->values()->all();
        $selectedOutputTypeCategoryIds = array_values(array_filter(array_map(
            'intval',
            (array) old('output_type_product_category_ids', [])
        ), static fn ($id) => $id > 0));
        $selectedOutputTypeSubCategoryIds = array_values(array_filter(array_map(
            'intval',
            (array) old('output_type_product_sub_category_ids', [])
        ), static fn ($id) => $id > 0));
        // Saved Output Products are shown in the Added Out Put Type Products table below.
        // Do not keep old saved checkboxes selected after a successful save.
        $selectedOutputTypeProductIds = array_values(array_filter(array_map(
            'intval',
            (array) old('output_type_product_ids', [])
        ), static fn ($id) => $id > 0));
    @endphp

    <div class="rcm-product-mapping-section-buttons-scroll" data-rcm-no-export>
        <div class="rcm-product-mapping-section-buttons" data-rcm-product-mapping-section-buttons>
            <button type="button" class="rcm-product-mapping-section-btn rcm-product-mapping-section-btn-category" data-rcm-settings-section-button="paddy-rice-mapping" aria-expanded="false">
                <i class="fa fa-link"></i>
                <span>Paddy &amp; Rice Product Category Mapping</span>
            </button>
            <button type="button" class="rcm-product-mapping-section-btn rcm-product-mapping-section-btn-added" data-rcm-settings-section-button="added-category-mapping" aria-expanded="false">
                <i class="fa fa-list"></i>
                <span>Added Product Category Mapping</span>
            </button>
            <button type="button" class="rcm-product-mapping-section-btn rcm-product-mapping-section-btn-packaging" data-rcm-settings-section-button="packaging-material" aria-expanded="false">
                <i class="fa fa-cubes"></i>
                <span>Select Packaging Material</span>
            </button>
            <button type="button" class="rcm-product-mapping-section-btn rcm-product-mapping-section-btn-output" data-rcm-settings-section-button="output-type" aria-expanded="false">
                <i class="fa fa-sign-out"></i>
                <span>Out Put Type</span>
            </button>
        </div>
    </div>

    <div class="rcm-settings-collapsible-stack">
        <details class="rcm-settings-collapsible" data-rcm-settings-section="paddy-rice-mapping" {{ ($errors->has('paddy_product_category_id') || $errors->has('paddy_payment_account_id') || $errors->has('rice_product_category_id') || $errors->has('rice_payment_account_id')) ? 'open' : '' }}>
            <summary>
                <span><i class="fa fa-link"></i> Paddy &amp; Rice Product Category Mapping</span>
                <i class="fa fa-chevron-down rcm-settings-collapse-icon"></i>
            </summary>
            <div class="rcm-settings-collapsible-body">
                <form method="post" action="{{ route('rice-mill.settings.product-category-mapping.save') }}">
                    @csrf
                    <div id="rcm-paddy-category-mapping-card" class="rcm-card rcm-category-mapping-edit-card" style="margin-bottom:14px">
                        <div class="rcm-category-mapping-card-head">
                            <div class="rcm-section-title">Paddy</div>
                            @if($paddyConfigured)
                                <span class="rcm-status-pill {{ $paddyEnabled ? 'active' : 'inactive' }}">{{ $paddyEnabled ? 'Enabled' : 'Disabled' }}</span>
                            @endif
                        </div>
                        <div class="rcm-form-grid">
                            <div class="rcm-field">
                                <label>Product Category</label>
                                <select class="rcm-searchable" name="paddy_product_category_id" required>
                                    <option value="">Select Product Category</option>
                                    @foreach($productCategories as $category)
                                        <option value="{{ $category['id'] }}" {{ $paddyCategoryId === (int)$category['id'] ? 'selected' : '' }}>{{ $category['name'] }}</option>
                                    @endforeach
                                </select>
                                <small>Type in the dropdown to auto-filter; scroll or use keyboard Up/Down arrows.</small>
                                <div class="rcm-muted" style="margin-top:5px">Selected: <strong>{{ $categoryNames[$paddyCategoryId] ?? 'Not mapped' }}</strong></div>
                            </div>
                            <div class="rcm-field">
                                <label>Payment Account</label>
                                <select class="rcm-searchable" name="paddy_payment_account_id" required>
                                    <option value="">Select Current Liabilities Account</option>
                                    @foreach($currentLiabilityAccounts as $account)
                                        <option value="{{ $account['id'] }}" {{ $paddyAccountId === (int)$account['id'] ? 'selected' : '' }}>{{ $account['name'] }}</option>
                                    @endforeach
                                </select>
                                <small>All open List Accounts under Current Liabilities are shown.</small>
                                <div class="rcm-muted" style="margin-top:5px">Selected: <strong>{{ $currentLiabilityNames[$paddyAccountId] ?? 'Not mapped' }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <div id="rcm-rice-category-mapping-card" class="rcm-card rcm-category-mapping-edit-card" style="margin-bottom:14px">
                        <div class="rcm-category-mapping-card-head">
                            <div class="rcm-section-title">Rice</div>
                            @if($riceConfigured)
                                <span class="rcm-status-pill {{ $riceEnabled ? 'active' : 'inactive' }}">{{ $riceEnabled ? 'Enabled' : 'Disabled' }}</span>
                            @endif
                        </div>
                        <div class="rcm-form-grid">
                            <div class="rcm-field">
                                <label>Product Category</label>
                                <select class="rcm-searchable" name="rice_product_category_id" required>
                                    <option value="">Select Product Category</option>
                                    @foreach($productCategories as $category)
                                        <option value="{{ $category['id'] }}" {{ $riceCategoryId === (int)$category['id'] ? 'selected' : '' }}>{{ $category['name'] }}</option>
                                    @endforeach
                                </select>
                                <small>Type in the dropdown to auto-filter; scroll or use keyboard Up/Down arrows.</small>
                                <div class="rcm-muted" style="margin-top:5px">Selected: <strong>{{ $categoryNames[$riceCategoryId] ?? 'Not mapped' }}</strong></div>
                            </div>
                            <div class="rcm-field">
                                <label>Payment Account</label>
                                <select class="rcm-searchable" name="rice_payment_account_id" required>
                                    <option value="">Select Current Liabilities Account</option>
                                    @foreach($currentLiabilityAccounts as $account)
                                        <option value="{{ $account['id'] }}" {{ $riceAccountId === (int)$account['id'] ? 'selected' : '' }}>{{ $account['name'] }}</option>
                                    @endforeach
                                </select>
                                <small>All open List Accounts under Current Liabilities are shown.</small>
                                <div class="rcm-muted" style="margin-top:5px">Selected: <strong>{{ $currentLiabilityNames[$riceAccountId] ?? 'Not mapped' }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="rcm-toolbar">
                        <button class="rcm-btn" type="submit" {{ count($productCategories ?? []) === 0 || count($currentLiabilityAccounts ?? []) === 0 ? 'disabled' : '' }}>
                            <i class="fa fa-save"></i> Save Product Category Mapping
                        </button>
                    </div>
                </form>
            </div>
        </details>

        <details class="rcm-settings-collapsible" data-rcm-settings-section="added-category-mapping">
            <summary>
                <span><i class="fa fa-list"></i> Added Product Category Mapping</span>
                <i class="fa fa-chevron-down rcm-settings-collapse-icon"></i>
            </summary>
            <div class="rcm-settings-collapsible-body">
                <p class="rcm-muted">Saved mappings remain here permanently. They can be edited or disabled/enabled, but they cannot be deleted.</p>
                <div class="rcm-table-wrap">
                    <table class="rcm-table">
                        <thead><tr><th>Mapping</th><th>Product Category</th><th>Payment Account</th><th>Status</th><th data-rcm-no-export>Action</th></tr></thead>
                        <tbody>
                            @if($paddyConfigured)
                                <tr>
                                    <td><strong>Paddy</strong></td>
                                    <td>{{ $categoryNames[$paddyCategoryId] ?? ('Category #'.$paddyCategoryId) }}</td>
                                    <td>{{ $currentLiabilityNames[$paddyAccountId] ?? ('Account #'.$paddyAccountId) }}</td>
                                    <td><span class="rcm-status-pill {{ $paddyEnabled ? 'active' : 'inactive' }}">{{ $paddyEnabled ? 'Enabled' : 'Disabled' }}</span></td>
                                    <td><div class="rcm-category-mapping-actions">
                                        <button type="button" class="rcm-btn secondary" data-rcm-edit-category-mapping="rcm-paddy-category-mapping-card"><i class="fa fa-pencil"></i> Edit</button>
                                        <form method="post" action="{{ route('rice-mill.settings.product-category-mapping.toggle','paddy') }}">@csrf<button type="submit" class="rcm-btn {{ $paddyEnabled ? 'danger' : '' }}"><i class="fa {{ $paddyEnabled ? 'fa-ban' : 'fa-check' }}"></i> {{ $paddyEnabled ? 'Disable' : 'Enable' }}</button></form>
                                    </div></td>
                                </tr>
                            @endif
                            @if($riceConfigured)
                                <tr>
                                    <td><strong>Rice</strong></td>
                                    <td>{{ $categoryNames[$riceCategoryId] ?? ('Category #'.$riceCategoryId) }}</td>
                                    <td>{{ $currentLiabilityNames[$riceAccountId] ?? ('Account #'.$riceAccountId) }}</td>
                                    <td><span class="rcm-status-pill {{ $riceEnabled ? 'active' : 'inactive' }}">{{ $riceEnabled ? 'Enabled' : 'Disabled' }}</span></td>
                                    <td><div class="rcm-category-mapping-actions">
                                        <button type="button" class="rcm-btn secondary" data-rcm-edit-category-mapping="rcm-rice-category-mapping-card"><i class="fa fa-pencil"></i> Edit</button>
                                        <form method="post" action="{{ route('rice-mill.settings.product-category-mapping.toggle','rice') }}">@csrf<button type="submit" class="rcm-btn {{ $riceEnabled ? 'danger' : '' }}"><i class="fa {{ $riceEnabled ? 'fa-ban' : 'fa-check' }}"></i> {{ $riceEnabled ? 'Disable' : 'Enable' }}</button></form>
                                    </div></td>
                                </tr>
                            @endif
                            @if(!$paddyConfigured && !$riceConfigured)
                                <tr><td colspan="5" class="rcm-empty">No Product Category Mapping has been added yet.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </details>

        <details class="rcm-settings-collapsible" data-rcm-settings-section="packaging-material" {{ ($errors->has('packaging_product_category_id') || $errors->has('packaging_product_sub_category_id') || $errors->has('packaging_product_ids') || $errors->has('packaging_product_ids.*')) ? 'open' : '' }}>
            <summary>
                <span><i class="fa fa-cubes"></i> Select Packaging Material</span>
                <i class="fa fa-chevron-down rcm-settings-collapse-icon"></i>
            </summary>
            <div class="rcm-settings-collapsible-body rcm-packaging-product-selection-section">
                <p class="rcm-muted">Select Products New Products to use as Rice Mill Packaging Materials. Product Category and Product Sub Category are optional filters.</p>
                <form method="post" action="{{ route('rice-mill.settings.packaging-material-product-selection.save') }}" data-rcm-product-master-selector data-selector-prefix="packaging">
                    @csrf
                    <div class="rcm-form-grid rcm-packaging-product-selector-grid">
                        <div class="rcm-field">
                            <label>Product Category</label>
                            <select id="rcm-packaging-product-category" class="rcm-searchable" name="packaging_product_category_id" data-rcm-master-category>
                                <option value="">All Product Categories</option>
                                @foreach($packagingCategories as $category)<option value="{{ $category['id'] }}" {{ $selectedPackagingCategoryId === (int)$category['id'] ? 'selected' : '' }}>{{ $category['name'] }}</option>@endforeach
                            </select>
                            <small>Optional. Type to auto-filter; use the scrollbar or keyboard Up/Down arrows.</small>
                            @error('packaging_product_category_id')<div class="rcm-field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="rcm-field">
                            <label>Product Sub Category</label>
                            <select id="rcm-packaging-product-sub-category" class="rcm-searchable" name="packaging_product_sub_category_id" data-rcm-master-subcategory>
                                <option value="">All Product Sub Categories</option>
                                @foreach($packagingSubCategories as $subCategory)
                                    <option value="{{ $subCategory['id'] }}" data-parent-id="{{ $subCategory['parent_id'] }}" {{ $selectedPackagingSubCategoryId === (int)$subCategory['id'] ? 'selected' : '' }}>{{ $subCategory['parent_name'] ? $subCategory['parent_name'].' > ' : '' }}{{ $subCategory['name'] }}</option>
                                @endforeach
                            </select>
                            <small>Optional. When Product Category is selected, only linked Sub Categories are shown.</small>
                            @error('packaging_product_sub_category_id')<div class="rcm-field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="rcm-field">
                            <label>Products <span class="rcm-required">*</span></label>
                            <select id="rcm-packaging-product" class="rcm-searchable rcm-packaging-product-multi" name="packaging_product_ids[]" multiple data-placeholder="Select one or more Products" data-rcm-master-products required>
                                @foreach($packagingProductOptions ?? [] as $product)
                                    <option value="{{ $product['id'] }}" data-category-id="{{ (int)($product['category_id'] ?? 0) }}" data-sub-category-id="{{ (int)($product['sub_category_id'] ?? 0) }}" {{ in_array((int)$product['id'], $selectedPackagingProductIds, true) ? 'selected' : '' }}>{{ !empty($product['code']) ? $product['code'].' - ' : '' }}{{ $product['name'] }}</option>
                                @endforeach
                            </select>
                            <small>Required. Multiple Products can be selected in one save. The list follows Category/Sub Category filters.</small>
                            @error('packaging_product_ids')<div class="rcm-field-error">{{ $message }}</div>@enderror
                            @error('packaging_product_ids.*')<div class="rcm-field-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="rcm-toolbar" style="margin-top:12px"><button class="rcm-btn" type="submit" {{ count($packagingProductOptions ?? []) === 0 ? 'disabled' : '' }}><i class="fa fa-save"></i> Save Packaging Materials</button></div>
                </form>
                <script type="application/json" data-rcm-master-selector-data data-selector-prefix="packaging">@json(['subcategories'=>$packagingSubCategories,'products'=>$packagingProductOptions ?? []])</script>

                <div class="rcm-category-mapping-list rcm-packaging-selection-list">
                    <div class="rcm-section-title">Saved Packaging Materials</div>
                    <div class="rcm-table-wrap">
                        <table class="rcm-table">
                            <thead><tr><th>Product Category</th><th>Product Sub Category</th><th>Product</th><th>Product Code</th><th>Unit</th><th>Packaging Material ID</th></tr></thead>
                            <tbody>
                                @forelse($packagingMaterialSelections ?? [] as $selection)
                                    @php
                                        $selectedMasterProduct = $packagingProductById->get((int)($selection['product_id'] ?? 0));
                                        $masterCategoryId = (int)($selectedMasterProduct['category_id'] ?? 0);
                                        $masterSubCategoryId = (int)($selectedMasterProduct['sub_category_id'] ?? 0);
                                    @endphp
                                    <tr>
                                        <td>{{ $packagingCategoryNames[$masterCategoryId] ?? '-' }}</td>
                                        <td>{{ $packagingSubCategoryNames[$masterSubCategoryId] ?? '-' }}</td>
                                        <td><strong>{{ $selectedMasterProduct['name'] ?? ('Product #'.(int)($selection['product_id'] ?? 0)) }}</strong></td>
                                        <td>{{ $selectedMasterProduct['code'] ?? '-' }}</td>
                                        <td>{{ $selectedMasterProduct['unit'] ?? 'pcs' }}</td>
                                        <td>{{ !empty($selection['material_id']) ? $selection['material_id'] : 'Auto sync pending' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="rcm-empty">No Packaging Material Product has been selected yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </details>

        <details class="rcm-settings-collapsible" data-rcm-settings-section="output-type" {{ ($errors->has('output_type_product_category_ids') || $errors->has('output_type_product_category_ids.*') || $errors->has('output_type_product_sub_category_ids') || $errors->has('output_type_product_sub_category_ids.*') || $errors->has('output_type_product_ids') || $errors->has('output_type_product_ids.*')) ? 'open' : '' }}>
            <summary>
                <span><i class="fa fa-sign-out"></i> Out Put Type</span>
                <i class="fa fa-chevron-down rcm-settings-collapse-icon"></i>
            </summary>
            <div class="rcm-settings-collapsible-body rcm-packaging-product-selection-section">
                <p class="rcm-muted">Only Products linked to the Products New Product Categories <strong>Rice</strong> and <strong>By Products</strong> are available below. Tick the Products that should be mapped as Rice Mill production Outputs.</p>

                @if(count($outputTypeEligibleCategoryIds ?? []) === 0)
                    <div class="rcm-alert rcm-alert-danger">
                        <i class="fa fa-exclamation-circle"></i>
                        <span>The Product Categories “Rice” and “By Products” were not found in Products New for this business.</span>
                    </div>
                @endif

                <form method="post" action="{{ route('rice-mill.settings.output-type-product-selection.save') }}" data-rcm-output-checkbox-selector>
                    @csrf

                    <div class="rcm-form-grid rcm-output-type-filter-grid">
                        <div class="rcm-field">
                            <label>Product Category</label>
                            <select id="rcm-output-type-product-category"
                                    class="rcm-searchable"
                                    name="output_type_product_category_ids[]"
                                    multiple
                                    data-placeholder="Select one or more Product Categories"
                                    data-rcm-output-category-filter>
                                @foreach($outputTypeCategories as $category)
                                    <option value="{{ $category['id'] }}" {{ in_array((int)$category['id'], $selectedOutputTypeCategoryIds, true) ? 'selected' : '' }}>{{ $category['name'] }}</option>
                                @endforeach
                            </select>
                            <small>Multiple Product Categories can be selected. Type to filter; use the scrollbar or keyboard Up/Down arrows.</small>
                            @error('output_type_product_category_ids')<div class="rcm-field-error">{{ $message }}</div>@enderror
                            @error('output_type_product_category_ids.*')<div class="rcm-field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="rcm-field">
                            <label>Product Sub Category / Product <span class="rcm-required">*</span></label>
                            <select id="rcm-output-type-product-sub-category"
                                    class="rcm-searchable"
                                    name="output_type_product_sub_category_ids[]"
                                    multiple
                                    required
                                    data-placeholder="Select Product Sub Categories"
                                    data-rcm-output-subcategory-filter>
                                @foreach($outputTypeSubCategories as $subCategory)
                                    <option value="{{ $subCategory['id'] }}"
                                            data-top-category-id="{{ (int)($subCategory['top_category_id'] ?? 0) }}"
                                            {{ in_array((int)$subCategory['id'], $selectedOutputTypeSubCategoryIds, true) ? 'selected' : '' }}>
                                        {{ !empty($subCategory['top_category_name']) ? $subCategory['top_category_name'].' > ' : '' }}{{ $subCategory['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <small>Compulsory. Shows Sub Categories linked to the selected Product Categories. Multiple selection is allowed.</small>
                            @error('output_type_product_sub_category_ids')<div class="rcm-field-error">{{ $message }}</div>@enderror
                            @error('output_type_product_sub_category_ids.*')<div class="rcm-field-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="rcm-output-product-box" data-rcm-output-product-box>
                        <div class="rcm-output-product-box-head">
                            <div>
                                <div class="rcm-section-title">Output Products</div>
                                <div class="rcm-muted">Only Products linked to the selected Product Sub Categories are shown below. Tick the Products that should appear in Mill Production → Output Type.</div>
                            </div>
                            <div class="rcm-output-product-selection-count" data-rcm-output-selected-count>0 selected</div>
                        </div>

                        <div class="rcm-output-product-box-tools" data-rcm-no-export>
                            <div class="rcm-output-product-search-wrap">
                                <i class="fa fa-search"></i>
                                <input type="search" placeholder="Type to filter Output Products..." data-rcm-output-product-search autocomplete="off">
                            </div>
                            <button type="button" class="rcm-btn secondary" data-rcm-output-select-visible><i class="fa fa-check-square-o"></i> Select Visible</button>
                            <button type="button" class="rcm-btn secondary" data-rcm-output-clear-visible><i class="fa fa-square-o"></i> Clear Visible</button>
                        </div>

                        <div class="rcm-output-product-checkbox-list" data-rcm-output-product-list>
                            @forelse($outputTypeProductOptions ?? [] as $product)
                                @php
                                    $outputTopCategoryId = (int)($product['top_category_id'] ?? 0);
                                    $outputSubCategoryId = (int)($product['sub_category_id'] ?? 0);
                                    $outputTopCategoryName = (string)($product['top_category_name'] ?? ($outputTypeCategoryNames[$outputTopCategoryId] ?? ''));
                                    $outputSubCategoryName = (string)($outputTypeSubCategoryNames[$outputSubCategoryId] ?? '');
                                @endphp
                                <label class="rcm-output-product-check"
                                       data-rcm-output-product-item
                                       data-category-id="{{ $outputTopCategoryId }}"
                                       data-sub-category-id="{{ $outputSubCategoryId }}"
                                       data-search="{{ strtolower(trim(($product['code'] ?? '').' '.($product['name'] ?? '').' '.$outputTopCategoryName.' '.$outputSubCategoryName)) }}">
                                    <input type="checkbox"
                                           name="output_type_product_ids[]"
                                           value="{{ (int)$product['id'] }}"
                                           {{ in_array((int)$product['id'], $selectedOutputTypeProductIds, true) ? 'checked' : '' }}>
                                    <span class="rcm-output-product-checkmark"><i class="fa fa-check"></i></span>
                                    <span class="rcm-output-product-check-content">
                                        <span class="rcm-output-product-check-name">{{ !empty($product['code']) ? $product['code'].' - ' : '' }}{{ $product['name'] }}</span>
                                        <span class="rcm-output-product-check-meta">
                                            <strong>{{ $outputTopCategoryName ?: '-' }}</strong>
                                            @if($outputSubCategoryName) <span>• {{ $outputSubCategoryName }}</span> @endif
                                            <span>• {{ $product['unit'] ?? 'pcs' }}</span>
                                        </span>
                                    </span>
                                </label>
                            @empty
                                <div class="rcm-empty">No active Products are available under the Rice or By Products Product Categories.</div>
                            @endforelse
                        </div>
                        <div class="rcm-empty rcm-output-filter-empty" data-rcm-output-filter-empty hidden>Select at least one Product Sub Category to show linked Products.</div>
                    </div>

                    @error('output_type_product_ids')<div class="rcm-field-error" style="margin-top:8px">{{ $message }}</div>@enderror
                    @error('output_type_product_ids.*')<div class="rcm-field-error" style="margin-top:8px">{{ $message }}</div>@enderror

                    <div class="rcm-toolbar" style="margin-top:12px">
                        <button class="rcm-btn" type="submit"><i class="fa fa-save"></i> Save Out Put Types</button>
                    </div>
                </form>

                <div class="rcm-category-mapping-list rcm-packaging-selection-list">
                    <div class="rcm-section-title">Added Out Put Type Products</div>
                    <p class="rcm-muted">These checked Products are the only Products shown in the Mill Production Output Type section.</p>
                    <div class="rcm-table-wrap">
                        <table class="rcm-table">
                            <thead><tr><th>Product Category</th><th>Product Sub Category</th><th>Product</th><th>Product Code</th><th>Unit</th></tr></thead>
                            <tbody>
                                @forelse($outputTypeSelections ?? [] as $selection)
                                    @php
                                        $selectedOutputProduct = $outputTypeProductById->get((int)($selection['product_id'] ?? 0));
                                        $outputCategoryId = (int)($selectedOutputProduct['top_category_id'] ?? $selection['category_id'] ?? 0);
                                        $outputSubCategoryId = (int)($selectedOutputProduct['sub_category_id'] ?? $selection['sub_category_id'] ?? 0);
                                    @endphp
                                    <tr>
                                        <td>{{ $selectedOutputProduct['top_category_name'] ?? ($outputTypeCategoryNames[$outputCategoryId] ?? '-') }}</td>
                                        <td>{{ $outputTypeSubCategoryNames[$outputSubCategoryId] ?? '-' }}</td>
                                        <td><strong>{{ $selectedOutputProduct['name'] ?? ('Product #'.(int)($selection['product_id'] ?? 0)) }}</strong></td>
                                        <td>{{ $selectedOutputProduct['code'] ?? '-' }}</td>
                                        <td>{{ $selectedOutputProduct['unit'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="rcm-empty">No Out Put Type Product has been selected yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </details>
    </div>
</section>
