@php
    /*
     * MA-002 (IS-1919 #4): true only on EDIT, and only once the product has
     * been used in a purchase or a sale. On the create form it is always
     * false, so nothing there changes.
     */
    $pnLocked = !empty($usedInTransactions);
@endphp

@if($pnLocked)
    <div class="alert alert-info" style="margin-bottom:16px;">
        <i class="fa fa-lock"></i>
        <strong>This product has been used in transactions.</strong>
        Category, Sub Category, Unit and Selling Price Tax can no
        longer be changed - existing purchase and sale lines depend on them. Every
        other detail is still editable.
    </div>
@endif

@php
    $pricing = $formState['pricing'] ?? [];
    $locations = collect($lookups['locations'] ?? []);
    $stores = collect($lookups['stores'] ?? []);
    $storesByLocation = $stores->groupBy(fn ($store) => (int) $store->location_id);
    $finishedGoodsAccount = collect($lookups['stockAccounts'] ?? [])->first(function ($account) {
        return strcasecmp(trim((string) ($account->name ?? '')), 'Finished Goods Account') === 0;
    });
    $selectedLocations = collect(old('product_locations', $formState['locations'] ?? []))
        ->map(fn ($id) => (string) $id)
        ->all();

    if (!$product->exists && empty($selectedLocations) && $locations->count() === 1) {
        $selectedLocations = [(string) $locations->first()->id];
    }

    $oldOpeningStock = old('opening_stock', []);
    $openingRowIndex = 0;
    $selectedCategoryId = (string) old('category_id', $product->category_id ?? '');
    $selectedSubCategoryId = (string) old('sub_category_id', $product->sub_category_id ?? '');
    $addedDate = old('date', !empty($product->date) ? substr((string) $product->date, 0, 10) : now()->toDateString());
    $currentImage = old('image_current', $product->image ?? '');
    $currentImageUrl = '';

    if ($currentImage) {
        $currentImageUrl = \Illuminate\Support\Str::startsWith($currentImage, ['http://', 'https://', '/'])
            ? $currentImage
            : asset('uploads/img/' . ltrim($currentImage, '/'));
    }
@endphp

<form method="post"
      action="{{ $action }}"
      class="pn-form pn-product-form"
      enctype="multipart/form-data"
      data-pn-product-wizard
      data-pn-existing-product="{{ $product->exists ? '1' : '0' }}">
    @csrf
    @if($method === 'put') @method('put') @endif

    @if ($errors->any())
        <div class="alert alert-danger pn-alert">
            <strong><i class="fa fa-exclamation-circle"></i> Please check the form.</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="pn-wizard-tabs" role="tablist" aria-label="Product form sections">
        <button type="button" class="active" data-pn-target="basic" role="tab" aria-selected="true"><span>1</span>Basic</button>
        <button type="button" data-pn-target="pricing" role="tab" aria-selected="false"><span>2</span>Pricing</button>
        <button type="button" data-pn-target="inventory" role="tab" aria-selected="false"><span>3</span>Inventory</button>
        <button type="button" data-pn-target="media" role="tab" aria-selected="false"><span>4</span>Images</button>
        <button type="button" data-pn-target="tax" role="tab" aria-selected="false"><span>5</span>Tax</button>
        <button type="button" data-pn-target="locations" role="tab" aria-selected="false"><span>6</span>Locations</button>
    </div>

    <div class="pn-form-section" data-pn-section="basic">
        <div class="pn-section-heading">
            <div><i class="fa fa-info-circle"></i></div>
            <div><h3>Basic Information</h3><p>Product identity, classification, codes and operational details.</p></div>
        </div>

        <div class="row pn-form-grid">
            <div class="col-md-6 form-group @error('name') has-error @enderror">
                <label>Product Name <span class="text-danger">*</span></label>
                <input class="form-control" name="name" value="{{ old('name', $product->name) }}" required>
                @error('name')<span class="help-block">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-3 form-group @error('type') has-error @enderror">
                <label>Product Type <span class="text-danger">*</span></label>
                <select class="form-control" name="type" data-pn-product-type required>
                    <option value="single" @selected(old('type', $product->type) === 'single')>Single</option>
                    <option value="variable" @selected(old('type', $product->type) === 'variable')>Variable</option>
                    <option value="combo" @selected(old('type', $product->type) === 'combo')>Combo / Bundle</option>
                </select>
                @error('type')<span class="help-block">{{ $message }}</span>@enderror
            </div>

            {{-- Show in Pumper Dashboard.

                 Appears only when the permission is enabled in
                 Super Admin > Manage New > Product New. A business that does
                 not run a pumper dashboard has no use for the field, and one
                 that shows for everyone is one everyone learns to ignore. --}}
            @if (! empty($lookups['show_pumper_dashboard_field']))
                <div class="col-md-3 form-group">
                    <label>Show in Pumper Dashboard</label>
                    <select class="form-control" name="show_in_pumper_dashboard">
                        <option value="1" @selected((int) old('show_in_pumper_dashboard', $product->show_in_pumper_dashboard ?? 1) === 1)>Yes</option>
                        <option value="0" @selected((int) old('show_in_pumper_dashboard', $product->show_in_pumper_dashboard ?? 1) === 0)>No</option>
                    </select>
                    @error('show_in_pumper_dashboard')<span class="help-block">{{ $message }}</span>@enderror
                </div>
            @endif

            <div class="col-md-3 form-group @error('sku') has-error @enderror">
                <label>SKU</label>
                @if($product->exists)
                    <input class="form-control pn-sku-locked" value="{{ $product->sku }}" readonly disabled aria-readonly="true" title="SKU cannot be changed after the product is created">
                    <small class="pn-sku-lock-help"><i class="fa fa-lock" aria-hidden="true"></i>SKU is locked after product creation.</small>
                @else
                    <input class="form-control" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="Auto or manual SKU">
                    <small class="pn-field-help">Leave blank to generate automatically.</small>
                    @error('sku')<span class="help-block">{{ $message }}</span>@enderror
                @endif
            </div>

            <div class="col-md-3 form-group @error('barcode') has-error @enderror">
                <label>Barcode</label>
                <input class="form-control" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}">
                @error('barcode')<span class="help-block">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-3 form-group">
                <label>Brand</label>
                <select class="form-control" name="brand_id">
                    <option value="">None</option>
                    @foreach(($lookups['brands'] ?? []) as $brand)
                        <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 form-group @error('category_id') has-error @enderror">
                <label>Category</label>
                <select class="form-control" name="category_id" data-pn-category @disabled($pnLocked) data-pn-lockable>
                    <option value="">None</option>
                    @foreach(($lookups['categories'] ?? []) as $category)
                        <option value="{{ $category->id }}" @selected($selectedCategoryId === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                    {{-- MA-002: a disabled select posts NOTHING, so the stored value
                         is submitted here instead. Without this, saving a locked
                         product would clear the very field we are protecting. --}}
                    @if($pnLocked)<input type="hidden" name="category_id" value="{{ old('category_id', $product->category_id) }}">@endif
                @error('category_id')<span class="help-block">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-3 form-group @error('sub_category_id') has-error @enderror">
                <label>Sub Category</label>
                <select class="form-control" name="sub_category_id" data-pn-subcategory data-selected="{{ $selectedSubCategoryId }}" @disabled($pnLocked) data-pn-lockable>
                    <option value="">Select category first</option>
                    @foreach(($lookups['subCategories'] ?? []) as $category)
                        <option value="{{ $category->id }}"
                                data-parent-id="{{ $category->parent_id ?? 0 }}"
                                @selected($selectedSubCategoryId === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                    {{-- MA-002: a disabled select posts NOTHING, so the stored value
                         is submitted here instead. Without this, saving a locked
                         product would clear the very field we are protecting. --}}
                    @if($pnLocked)<input type="hidden" name="sub_category_id" value="{{ old('sub_category_id', $product->sub_category_id) }}">@endif
                <small class="pn-field-help">Only subcategories belonging to the selected category are shown.</small>
                @error('sub_category_id')<span class="help-block">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-3 form-group">
                <label>Unit</label>
                <select class="form-control" name="unit_id" @disabled($pnLocked) data-pn-lockable>
                    <option value="">None</option>
                    @foreach(($lookups['units'] ?? []) as $unit)
                        <option value="{{ $unit->id }}" @selected(old('unit_id', $product->unit_id) == $unit->id)>{{ $unit->actual_name ?? $unit->short_name }} ({{ $unit->short_name }})</option>
                    @endforeach
                </select>
                    {{-- MA-002: a disabled select posts NOTHING, so the stored value
                         is submitted here instead. Without this, saving a locked
                         product would clear the very field we are protecting. --}}
                    @if($pnLocked)<input type="hidden" name="unit_id" value="{{ old('unit_id', $product->unit_id) }}">@endif
            </div>

            <div class="col-md-3 form-group">
                <label>Warranty</label>
                <select class="form-control" name="warranty_id">
                    <option value="">None</option>
                    @foreach(($lookups['warranties'] ?? []) as $warranty)
                        <option value="{{ $warranty->id }}" @selected(old('warranty_id', $product->warranty_id) == $warranty->id)>{{ $warranty->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 form-group @error('weight') has-error @enderror">
                <label>Weight</label>
                <input class="form-control" name="weight" value="{{ old('weight', $product->weight) }}">
                @error('weight')<span class="help-block">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-3 form-group @error('preparation_time_in_minutes') has-error @enderror">
                <label>Preparation Time in Minutes</label>
                <input type="number" min="0" step="1" class="form-control" name="preparation_time_in_minutes" value="{{ old('preparation_time_in_minutes', $product->preparation_time_in_minutes ?? '') }}">
                @error('preparation_time_in_minutes')<span class="help-block">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-3 form-group @error('date') has-error @enderror">
                <label>Added Date</label>
                <input type="date" class="form-control" name="date" value="{{ $addedDate }}">
                @error('date')<span class="help-block">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    <div class="pn-form-section" data-pn-section="pricing" hidden>
        <div class="pn-section-heading">
            <div><i class="fa fa-money"></i></div>
            <div><h3>Pricing</h3><p>Default purchase and selling prices for the base product variation.</p></div>

        {{--
            MA-002: Applicable Tax, moved here from the Tax tab.

            This is the field that decides whether the inclusive prices below are
            calculated at all, so it belongs beside them. On the separate tab an
            operator could type prices without ever seeing which tax was applied.

            "Tax Not Applicable" is the explicit choice for products that carry no
            tax. It posts the same empty value the old "None" did - so nothing about
            what gets stored has changed - but it says plainly that no tax applies,
            rather than leaving the reader to wonder whether "None" meant "no tax" or
            "not chosen yet". It is listed FIRST and selected by default when a
            product has no tax set.
        --}}
        {{--
            MA-002: the number of decimals the CALCULATED figures are rounded to,
            from Settings > Business Settings > Business > Currency Precision.
            Values the operator TYPES are never touched - the inputs now carry
            step="any" so the browser accepts any number of decimals.
        --}}
        <input type="hidden" data-pn-precision value="{{ (int) ($lookups['currencyPrecision'] ?? 2) }}">

        <div class="row pn-form-grid">
            <div class="col-md-4 form-group @error('tax') has-error @enderror">
                <label>Applicable Tax</label>
                {{--
                    MA-002 (feedback): data-no-search keeps this as a plain HTML
                    select instead of a select2.

                    select2 treats a first option with an EMPTY value as the
                    placeholder - selectPlaceholder() in productsnew.js reads its
                    text - so "Tax Not Applicable" was rendered grey and dropped
                    out of the list entirely once a real tax was chosen. There was
                    then no way back to it.

                    A tax list is short and does not need a search box, so the
                    simplest correct answer is not to enhance this one. As a plain
                    select, "Tax Not Applicable" is a normal choice that is always
                    visible and always selectable, and it still posts the same
                    empty value so products.tax stores NULL exactly as before.
                --}}
                <select class="form-control" name="tax" data-pn-tax data-no-search>
                    <option value="" data-rate="0" @selected(old('tax', $product->tax) === null || old('tax', $product->tax) === '')>
                        Tax Not Applicable
                    </option>
                    @foreach(($lookups['taxRates'] ?? []) as $tax)
                        <option value="{{ $tax->id }}" data-rate="{{ $tax->amount ?? 0 }}" @selected(old('tax', $product->tax) == $tax->id)>
                            {{ $tax->name }}{{ isset($tax->amount) ? ' (' . $tax->amount . '%)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('tax')<span class="help-block">{{ $message }}</span>@enderror
                <p class="help-block" style="margin-top:6px;">
                    Choose <strong>Tax Not Applicable</strong> if this product carries no tax.
                    The inclusive prices below are calculated from this.
                </p>
            </div>
        </div>
        </div>

        <div class="pn-price-panel">
            <div class="row pn-form-grid">
                <div class="col-md-3 form-group pn-price-col @error('single_dpp') has-error @enderror">
                    <label>Default Purchase Price (Excl. Tax)</label>
                    <input type="number" step="any" class="form-control" name="single_dpp" data-pn-price="purchase-ex" value="{{ old('single_dpp', $pricing['single_dpp'] ?? '') }}">
                    @error('single_dpp')<span class="help-block">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-3 form-group pn-price-col @error('single_dpp_inc_tax') has-error @enderror">
                    <label>Default Purchase Price (Incl. Tax)</label>
                    <input type="number" step="any" class="form-control" name="single_dpp_inc_tax" data-pn-price="purchase-inc" value="{{ old('single_dpp_inc_tax', $pricing['single_dpp_inc_tax'] ?? '') }}">
                    @error('single_dpp_inc_tax')<span class="help-block">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-2 form-group pn-price-col @error('profit_percent') has-error @enderror">
                    <label>Profit %</label>
                    @php
                        /*
                         * Default margin of 20% on a NEW product.
                         *
                         * Order matters: an old() value wins on a failed save so the
                         * operator's own figure is never replaced; a saved product keeps
                         * whatever margin it holds, INCLUDING a deliberate 0; and only a
                         * genuinely new form falls through to 20.
                         *
                         * The selling price is not pre-filled here - the calculator fills
                         * it as soon as a purchase price is entered, so the 20% shows its
                         * effect without inventing a price for a product that has no cost
                         * yet.
                         */
                        $pnProfitPercent = old('profit_percent', $pricing['profit_percent'] ?? null);

                        if ($pnProfitPercent === null || $pnProfitPercent === '') {
                            $pnProfitPercent = empty($product->id) ? 20 : '';
                        }
                    @endphp
                    <input type="number" step="any" class="form-control" name="profit_percent" data-pn-price="profit" value="{{ $pnProfitPercent }}">
                    @error('profit_percent')<span class="help-block">{{ $message }}</span>@enderror
                </div>
                {{--
                    MA-002: the basis the Profit % is measured on.

                    Tax Exclusive  - margin between the two Excl. figures
                    Tax Inclusive  - margin between the two Incl. figures

                    These give the SAME number whenever purchase and selling are
                    taxed at the same rate, because the same factor cancels out of
                    both sides of the division. They differ only when "Selling Price
                    Tax" on the Tax tab is set to something other than the Applicable
                    Tax - which is exactly why that field is now wired into the
                    calculation as well.

                    SAVED PER PRODUCT, in products.profit_basis. Without it the stored
                    variations.profit_percent would be one number that meant two
                    different real margins depending on an unremembered setting, and any
                    report reading that column would be wrong half the time.

                    Products saved before the column existed read back as exclusive,
                    which is how their figure was actually measured.
                --}}
                <div class="col-md-2 form-group pn-price-col @error('profit_basis') has-error @enderror">
                    <label>Profit Percentage On</label>
                    @php
                        /* Saved per product. Falls back to exclusive for every product
                           saved before the column existed, which is how the figure was
                           measured then. */
                        /*
                         * New products default to TAX INCLUSIVE, so the margin is applied
                         * to the purchase price including tax and the resulting selling
                         * price including tax is what the calculator fills in.
                         *
                         * A saved product keeps its own stored basis, so nothing already
                         * priced is re-interpreted. Where no tax applies the two bases are
                         * arithmetically identical - the same factor cancels from both
                         * sides of the division - so this only changes behaviour on taxed
                         * products, which is exactly where it was asked for.
                         */
                        /*
                         * IS2212:
                         * Read the saved basis from the form state first. ProductLookupService
                         * now resolves it from the Products New meta record as a compatibility
                         * fallback when an older tenant has not yet added products.profit_basis.
                         * This prevents a saved Tax Inclusive choice reopening as Exclusive.
                         */
                        $pnSavedProfitBasis = $pricing['profit_basis']
                            ?? $product->profit_basis
                            ?? (($product->tax_type ?? 'exclusive') === 'inclusive' ? 'inclusive' : null)
                            ?? (empty($product->id) ? 'inclusive' : 'exclusive');

                        $pnProfitBasis = strtolower((string) old('profit_basis', $pnSavedProfitBasis));
                        $pnProfitBasis = $pnProfitBasis === 'inclusive' ? 'inclusive' : 'exclusive';
                    @endphp
                    <select class="form-control" name="profit_basis" data-pn-profit-basis data-pn-saved-profit-basis="{{ $pnProfitBasis }}">
                        <option value="exclusive" @selected($pnProfitBasis === 'exclusive')>Tax Exclusive</option>
                        <option value="inclusive" @selected($pnProfitBasis === 'inclusive')>Tax Inclusive</option>
                    </select>
                    @error('profit_basis')<span class="help-block">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-2 form-group pn-price-col @error('single_dsp') has-error @enderror">
                    <label>Default Selling Price (Excl. Sales Tax)</label>
                    <input type="number" step="any" class="form-control" name="single_dsp" data-pn-price="selling-ex" value="{{ old('single_dsp', $pricing['single_dsp'] ?? '') }}">
                    @error('single_dsp')<span class="help-block">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-2 form-group pn-price-col @error('single_dsp_inc_tax') has-error @enderror">
                    <label>Default Selling Price (Incl. Sales Tax)</label>
                    <input type="number" step="any" class="form-control" name="single_dsp_inc_tax" data-pn-price="selling-inc" value="{{ old('single_dsp_inc_tax', $pricing['single_dsp_inc_tax'] ?? '') }}">
                    @error('single_dsp_inc_tax')<span class="help-block">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="pn-price-note" data-pn-price-note><i class="fa fa-calculator"></i>Selling price is calculated automatically from purchase price and profit percentage when it is left blank.</div>
        </div>
    </div>

    <div class="pn-form-section" data-pn-section="inventory" hidden>
        <div class="pn-section-heading">
            <div><i class="fa fa-cubes"></i></div>
            <div><h3>Inventory & Opening Stock</h3><p>Stock account, stock controls, reorder level and opening quantity by location or store.</p></div>
        </div>

        <div class="row pn-form-grid">
            <div class="col-md-3 form-group @error('stock_type') has-error @enderror">
                <label>Stock Account</label>
                @if($finishedGoodsAccount)
                    <input class="form-control" value="Finished Goods Account" readonly aria-readonly="true">
                    <input type="hidden" name="stock_type" value="{{ $finishedGoodsAccount->id }}">
                    <small class="pn-field-help"><i class="fa fa-lock"></i> Products New stock is always valued in Finished Goods Account, including Fuel products.</small>
                @else
                    <input class="form-control" value="Finished Goods Account not configured" readonly aria-readonly="true">
                    <div class="alert alert-danger" style="margin:8px 0 0;padding:8px 10px;">Configure <strong>Finished Goods Account</strong> in Finance / List Accounts before saving this product.</div>
                @endif
                @error('stock_type')<span class="help-block">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-3 form-group @error('alert_quantity') has-error @enderror">
                <label>Alert Quantity</label>
                <input type="number" step="0.001" min="0" class="form-control" name="alert_quantity" value="{{ old('alert_quantity', $product->alert_quantity) }}">
                @error('alert_quantity')<span class="help-block">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-4 form-group pn-check-group pn-inventory-switches">
                <label class="pn-switch-row">
                    <input type="checkbox" name="enable_stock" value="1" data-pn-enable-stock @checked(old('enable_stock', $product->enable_stock ?? 1))>
                    <span></span><em>Manage Stock?</em>
                </label>
                <label class="pn-switch-row">
                    <input type="checkbox" name="not_for_selling" value="1" @checked(old('not_for_selling', $product->not_for_selling))>
                    <span></span><em>Not For Selling</em>
                </label>
            </div>

            @if(!$product->exists)
                <div class="col-md-2 form-group">
                    <label>Opening Date</label>
                    <input type="date" class="form-control" name="opening_stock_date" value="{{ old('opening_stock_date', now()->toDateString()) }}">
                </div>
                <div class="col-md-2 form-group">
                    <label>Reference</label>
                    <input class="form-control" name="opening_stock_reference" value="{{ old('opening_stock_reference') }}" placeholder="Auto">
                </div>
            @endif
        </div>

        @if(!$product->exists)
            <div class="pn-opening-stock-panel" data-pn-opening-panel>
                <div class="pn-opening-stock-head">
                    <div><h4><i class="fa fa-archive"></i> Opening Stock</h4><p>Enter quantities only where opening stock is required. Zero or blank rows are ignored.</p></div>
                    <div class="pn-opening-total">Total Qty <strong data-pn-opening-total>0.000</strong></div>
                </div>

                @if($locations->isEmpty())
                    <div class="pn-empty-inline"><i class="fa fa-map-marker"></i>No business locations are available. Add a location before entering opening stock.</div>
                @else
                    <div class="table-responsive">
                        <table class="table pn-opening-stock-table">
                            <thead><tr><th>Location</th><th>Store</th><th class="pn-number-col">Opening Qty</th><th class="pn-number-col">Unit Cost</th><th class="pn-number-col">Value</th></tr></thead>
                            <tbody>
                                @foreach($locations as $location)
                                    @php $locationStores = collect($storesByLocation->get((int) $location->id, collect())); @endphp
                                    @if($locationStores->isEmpty())
                                        @php $rowOld = $oldOpeningStock[$openingRowIndex] ?? []; @endphp
                                        <tr data-pn-opening-row data-location-id="{{ $location->id }}">
                                            <td><strong>{{ $location->name }}</strong><input type="hidden" name="opening_stock[{{ $openingRowIndex }}][location_id]" value="{{ $location->id }}"></td>
                                            <td><span class="pn-location-only">Location stock</span></td>
                                            <td><input type="number" step="0.001" min="0" class="form-control pn-opening-qty" name="opening_stock[{{ $openingRowIndex }}][qty]" value="{{ $rowOld['qty'] ?? '' }}" placeholder="0.000"></td>
                                            <td><input type="number" step="any" min="0" class="form-control pn-opening-cost" name="opening_stock[{{ $openingRowIndex }}][unit_cost]" value="{{ $rowOld['unit_cost'] ?? old('single_dpp_inc_tax', $pricing['single_dpp_inc_tax'] ?? '') }}" placeholder="0.0000"></td>
                                            <td class="pn-opening-value" data-pn-opening-value>0.0000</td>
                                        </tr>
                                        @php $openingRowIndex++; @endphp
                                    @else
                                        @foreach($locationStores as $store)
                                            @php $rowOld = $oldOpeningStock[$openingRowIndex] ?? []; @endphp
                                            <tr data-pn-opening-row data-location-id="{{ $location->id }}">
                                                <td><strong>{{ $location->name }}</strong><input type="hidden" name="opening_stock[{{ $openingRowIndex }}][location_id]" value="{{ $location->id }}"></td>
                                                <td><span class="pn-store-name"><i class="fa fa-cube"></i> {{ $store->name }}</span><input type="hidden" name="opening_stock[{{ $openingRowIndex }}][store_id]" value="{{ $store->id }}"></td>
                                                <td><input type="number" step="0.001" min="0" class="form-control pn-opening-qty" name="opening_stock[{{ $openingRowIndex }}][qty]" value="{{ $rowOld['qty'] ?? '' }}" placeholder="0.000"></td>
                                                <td><input type="number" step="any" min="0" class="form-control pn-opening-cost" name="opening_stock[{{ $openingRowIndex }}][unit_cost]" value="{{ $rowOld['unit_cost'] ?? old('single_dpp_inc_tax', $pricing['single_dpp_inc_tax'] ?? '') }}" placeholder="0.0000"></td>
                                                <td class="pn-opening-value" data-pn-opening-value>0.0000</td>
                                            </tr>
                                            @php $openingRowIndex++; @endphp
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @else
            <div class="pn-info-banner"><i class="fa fa-info-circle"></i>Existing stock is not changed from the Edit Product form. Use Stock Centre or an inventory adjustment to preserve the audit trail.</div>
        @endif
    </div>

    <div class="pn-form-section" data-pn-section="media" hidden>
        <div class="pn-section-heading">
            <div><i class="fa fa-picture-o"></i></div>
            <div><h3>Product Image & Description</h3><p>Upload the primary image and maintain customer-facing product information.</p></div>
        </div>

        <div class="row pn-form-grid">
            <div class="col-md-5 form-group @error('image') has-error @enderror">
                <label>Product Image</label>
                <input type="file" class="form-control pn-file-input" name="image" accept="image/jpeg,image/png,image/webp,image/gif" data-pn-image-input>
                <input type="hidden" name="image_current" value="{{ $currentImage }}">
                <small class="pn-field-help">JPG, PNG, WEBP or GIF. Maximum 5 MB. Recommended square image.</small>
                @error('image')<span class="help-block">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-3 form-group">
                <label>Image Preview</label>
                <div class="pn-product-image-preview {{ $currentImageUrl ? 'has-image' : '' }}" data-pn-image-preview>
                    @if($currentImageUrl)<img src="{{ $currentImageUrl }}" alt="Current product image">@else<i class="fa fa-picture-o"></i><span>No image selected</span>@endif
                </div>
            </div>
            <div class="col-md-12 form-group @error('product_description') has-error @enderror">
                <label>Product Description</label>
                <textarea class="form-control" name="product_description" rows="6">{{ old('product_description', $product->product_description) }}</textarea>
                @error('product_description')<span class="help-block">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    <div class="pn-form-section" data-pn-section="tax" hidden>
        <div class="pn-section-heading">
            <div><i class="fa fa-percent"></i></div>
            <div><h3>Tax Settings</h3><p>Applicable purchase tax, selling-price tax and inclusive or exclusive treatment.</p></div>
        </div>

        <div class="row pn-form-grid">
            <div class="col-md-3 form-group">
                {{-- MA-002: "Applicable Tax" now lives in the Pricing section, because
                     that is where it changes what the operator sees - it drives the
                     inclusive price calculation. Having it on a separate tab meant
                     entering prices without knowing which tax was applied.

                     Deliberately MOVED rather than copied: two selects posting the
                     same name="tax" would submit twice and the last one would silently
                     win. --}}
                <label>Applicable Tax</label>
                <p class="help-block" style="margin-top:6px;">
                    Set on the <strong>Pricing</strong> tab, alongside the prices it affects.
                </p>
            </div>

            <div class="col-md-3 form-group @error('tax_type') has-error @enderror">
                <label>Selling Price Tax Type</label>
                {{--
                    MA-002 (IS-1941): NO "None" option here, deliberately.

                    tax_type is enum('inclusive','exclusive') NOT NULL. MySQL
                    rejects any other value, so a "None" option would look right
                    and then fail on save with an SQL error - worse than not
                    offering it.

                    Instead, when Selling Price Tax is set to None this field is
                    disabled and shown as not applicable, because with a zero
                    rate inclusive and exclusive give the same price. The stored
                    value stays valid, and the hidden input below keeps it
                    submitted while the select is disabled - a disabled select
                    posts nothing.
                --}}
                <select class="form-control" name="tax_type" data-pn-tax-type required>
                    <option value="exclusive" @selected(old('tax_type', $product->tax_type) === 'exclusive')>Exclusive</option>
                    <option value="inclusive" @selected(old('tax_type', $product->tax_type) === 'inclusive')>Inclusive</option>
                </select>
                {{-- Keeps tax_type submitted while the select is disabled. --}}
                <input type="hidden" name="tax_type" data-pn-tax-type-fallback
                       value="{{ old('tax_type', $product->tax_type ?: 'exclusive') }}" disabled>
                <p class="help-block" data-pn-tax-type-note style="display:none; margin-top:6px;">
                    Not applicable - this product has no selling tax.
                </p>
                @error('tax_type')<span class="help-block">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-3 form-group @error('sale_tax') has-error @enderror">
                <label>Selling Price Tax</label>
                {{-- MA-002: data-pn-sale-tax lets the price calculator use this rate
                     for the SELLING prices. Until now the field existed but was never
                     read, so selling prices were always taxed at the Applicable Tax
                     rate no matter what was chosen here. --}}
                {{-- MA-002: same reasoning - "Use Applicable Tax" is a real choice
                     here, not a placeholder, and must stay selectable. --}}
                <select class="form-control" name="sale_tax" data-pn-sale-tax data-no-search @disabled($pnLocked) data-pn-lockable>
                    <option value="" data-rate="">Use Applicable Tax</option>
                    {{--
                        MA-002 (IS-1941): "None" - this product carries NO selling tax.

                        data-rate="0" is what makes it work: the price calculator
                        reads the rate from the selected option's data-rate
                        attribute, so a zero here flows through the inclusive and
                        exclusive price maths without any change to the script.

                        It is deliberately DISTINCT from "Use Applicable Tax":

                            Use Applicable Tax  inherit whatever the product's
                                                Applicable Tax is
                            None                no tax, whatever that is

                        The value 0 is safe in sale_tax, which is int(11) - and it
                        cannot collide with a real tax, because tax_rates ids start
                        at 1.
                    --}}
                    <option value="0" data-rate="0" @selected(old('sale_tax', $product->sale_tax ?? '') === '0' || (string) old('sale_tax', $product->sale_tax ?? '') === '0')>None</option>
                    @foreach(($lookups['taxRates'] ?? []) as $tax)
                        <option value="{{ $tax->id }}" data-rate="{{ $tax->amount ?? 0 }}" @selected(old('sale_tax', $product->sale_tax ?? '') == $tax->id)>{{ $tax->name }} {{ isset($tax->amount) ? '(' . $tax->amount . '%)' : '' }}</option>
                    @endforeach
                </select>
                    {{-- MA-002: a disabled select posts NOTHING, so the stored value
                         is submitted here instead. Without this, saving a locked
                         product would clear the very field we are protecting. --}}
                    @if($pnLocked)<input type="hidden" name="sale_tax" value="{{ old('sale_tax', $product->sale_tax) }}">@endif
                @error('sale_tax')<span class="help-block">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-3 form-group pn-check-group pn-tax-switches">
                <label class="pn-switch-row">
                    <input type="checkbox" name="vat_claimed" value="1" @checked(old('vat_claimed', $product->vat_claimed ?? 1))>
                    <span></span><em>VAT Input Claimed</em>
                </label>
            </div>
        </div>
    </div>

    <div class="pn-form-section" data-pn-section="locations" hidden>
        <div class="pn-section-heading">
            <div><i class="fa fa-map-marker"></i></div>
            <div><h3>Product Locations</h3><p>Select the locations where this product will be available.</p></div>
        </div>

        <input type="hidden" name="product_locations_present" value="1">

        @if($locations->isEmpty())
            <div class="pn-empty-inline"><i class="fa fa-map-marker"></i>No business locations are available.</div>
        @else
            <div class="pn-location-grid">
                @foreach($locations as $location)
                    <label class="pn-location-option">
                        <input type="checkbox" name="product_locations[]" value="{{ $location->id }}" data-pn-location-checkbox="{{ $location->id }}" @checked(in_array((string) $location->id, $selectedLocations, true))>
                        <span class="pn-location-check"><i class="fa fa-check"></i></span>
                        <span class="pn-location-copy"><strong>{{ $location->name }}</strong><small>{{ collect($storesByLocation->get((int) $location->id, collect()))->count() }} store(s)</small></span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>

    <div class="pn-form-footer">
        <div class="pn-footer-navigation">
            <button type="button" class="pn-btn pn-btn-light" data-pn-prev disabled><i class="fa fa-chevron-left"></i> Previous</button>
            <button type="button" class="pn-btn pn-btn-light" data-pn-next>Next <i class="fa fa-chevron-right"></i></button>
        </div>
        <div class="pn-footer-actions">
            <a class="pn-btn pn-btn-light" href="{{ route('products-new.products.index') }}"><i class="fa fa-times"></i> Cancel</a>
            <button type="submit" class="pn-btn pn-btn-primary"><i class="fa fa-save"></i> Save Product</button>
        </div>
    </div>
</form>
