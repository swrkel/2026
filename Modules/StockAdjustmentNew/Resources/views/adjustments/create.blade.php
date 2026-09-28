@extends('stockadjustmentnew::layouts.app')

@section('san_title', 'Create Stock Adjustment')

@section('san_content')
@php
    $lineDefaults = [
        'product_id' => '',
        'variation_id' => '',
        'product_name' => '',
        'sku' => '',
        'system_qty' => '0',
        'counted_qty' => '0',
        'unit_cost' => '0',
        'batch_no' => '',
        'expiry_date' => '',
        'stock_adjustment_type' => 'increase',
    ];
    $lines = old('lines', [$lineDefaults]);
    $lines = is_array($lines) && count($lines) ? $lines : [$lineDefaults];
@endphp

@if ($errors->any())
    <div class="alert alert-danger san-validation-summary">
        <strong>Please correct the following:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form
    method="POST"
    action="{{ route('stock-adjustment-new.adjustments.store') }}"
    class="san-panel san-adjustment-form"
    data-san-adjustment-form
    data-product-search-url="{{ route('stock-adjustment-new.product-lookup.search') }}"
    data-batch-search-url="{{ route('stock-adjustment-new.batch-lookup.search') }}"
    data-next-line-index="{{ count($lines) }}"
>
    @csrf

    <div class="row san-adjustment-header-row">
        <div class="col-md-3">
            <label for="san-adjustment-date">Date</label>
            <input
                id="san-adjustment-date"
                name="adjustment_date"
                type="date"
                value="{{ old('adjustment_date', date('Y-m-d')) }}"
                class="form-control"
                required
            >
        </div>
        <div class="col-md-3">
            <label for="san-adjustment-type">Type</label>
            <select id="san-adjustment-type" name="adjustment_type" class="form-control san-static-select2" data-san-static-select2 required>
                @foreach (['quantity' => 'Quantity', 'value' => 'Value', 'damage' => 'Damage', 'expiry' => 'Expiry'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('adjustment_type', $settings['default_adjustment_type'] ?? 'quantity') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="san-location-id">Location</label>
            <select
                id="san-location-id"
                name="location_id"
                class="form-control san-static-select2"
                data-san-location-id
                data-san-static-select2
                required
            >
                <option value="">Please select location</option>
                @foreach($locations as $location)
                    <option value="{{ $location['id'] }}" @selected((string) old('location_id', $defaultLocationId) === (string) $location['id'])>
                        {{ $location['name'] }} (ID: {{ $location['id'] }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="san-store-id">Store</label>
            <select
                id="san-store-id"
                name="store_id"
                class="form-control san-static-select2"
                data-san-store-id
                data-san-static-select2
                @if((bool) ($settings['require_store'] ?? false)) required @endif
            >
                <option value="">{{ (bool) ($settings['require_store'] ?? false) ? 'Please select store' : 'All / no store' }}</option>
                @foreach($stores as $store)
                    <option
                        value="{{ $store['id'] }}"
                        data-location-id="{{ $store['location_id'] }}"
                        @selected((string) old('store_id', $defaultStoreId) === (string) $store['id'])
                    >
                        {{ $store['name'] }} (ID: {{ $store['id'] }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @if(empty($locations))
        <div class="alert alert-danger san-reference-warning">
            No business location is available for this user. Add/assign a Business Location before creating a Stock Adjustment.
        </div>
    @endif

    <div class="alert alert-info san-adjustment-direction-help">
        Select <strong>Increase</strong> or <strong>Decrease</strong> for every product line.
        For Increase, Counted Qty must be greater than System Qty. For Decrease, Counted Qty must be lower than System Qty.
    </div>

    <div class="row">
        <div class="col-md-6">
            <label for="san-reason-id">Reason</label>
            <select id="san-reason-id" name="reason_id" class="form-control" @if((bool) ($settings['require_reason'] ?? false)) required @endif>
                <option value="">Please select</option>
                @foreach ($reasons as $reason)
                    <option value="{{ $reason->id }}" @selected((string) old('reason_id') === (string) $reason->id)>
                        {{ $reason->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label for="san-notes">Notes</label>
            <input id="san-notes" name="notes" value="{{ old('notes') }}" class="form-control">
        </div>
    </div>

    <div class="row san-product-filter-row">
        <div class="col-md-6">
            <label for="san-product-category-filter">Product Category</label>
            <select
                id="san-product-category-filter"
                name="product_category_filter"
                class="form-control san-static-select2"
                data-san-product-category-filter
                data-san-static-select2
            >
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category['id'] }}" @selected((string) old('product_category_filter') === (string) $category['id'])>
                        {{ $category['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label for="san-product-sub-category-filter">Product Sub Category</label>
            <select
                id="san-product-sub-category-filter"
                name="product_sub_category_filter"
                class="form-control san-static-select2"
                data-san-product-sub-category-filter
                data-san-static-select2
            >
                <option value="">All sub categories</option>
                @foreach ($subCategories as $subCategory)
                    <option
                        value="{{ $subCategory['id'] }}"
                        data-parent-id="{{ $subCategory['parent_id'] }}"
                        @selected((string) old('product_sub_category_filter') === (string) $subCategory['id'])
                    >
                        {{ $subCategory['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @if (empty($categories))
        <div class="alert alert-warning san-reference-warning">
            No product categories were found for this business. Product lookup remains available without a category filter.
        </div>
    @endif

    <hr>

    <div class="san-lines-heading">
        <div>
            <h4>Lines</h4>
            <p>Select the product using <strong>Product ID / SKU</strong> or <strong>Product Name</strong>. Available batch numbers with positive stock will then load automatically.</p>
        </div>
    </div>

    <div class="table-responsive san-lines-table-wrap">
        <table class="table table-bordered san-lines-table" id="san-lines">
            <thead>
                <tr>
                    <th>Product ID / SKU</th>
                    <th>Product Name</th>
                    <th>Batch Number</th>
                    <th>Adjustment Type</th>
                    <th>System Qty</th>
                    <th>Counted Qty</th>
                    <th>Unit Cost</th>
                    <th class="san-line-action-column">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $index => $oldLine)
                    @php
                        $line = array_merge($lineDefaults, is_array($oldLine) ? $oldLine : []);
                        $productCode = trim((string) ($line['sku'] ?: $line['product_id']));
                    @endphp
                    <tr data-san-line-row>
                        <td class="san-product-cell">
                            <input
                                type="hidden"
                                name="lines[{{ $index }}][product_id]"
                                value="{{ $line['product_id'] }}"
                                data-san-product-id
                            >
                            <input
                                type="hidden"
                                name="lines[{{ $index }}][variation_id]"
                                value="{{ $line['variation_id'] }}"
                                data-san-variation-id
                            >
                            <input
                                type="hidden"
                                name="lines[{{ $index }}][sku]"
                                value="{{ $line['sku'] }}"
                                data-san-product-sku
                            >
                            <div class="san-product-lookup">
                                <input
                                    type="text"
                                    value="{{ $productCode }}"
                                    class="form-control"
                                    placeholder="Type ID or SKU"
                                    autocomplete="off"
                                    spellcheck="false"
                                    required
                                    aria-autocomplete="list"
                                    aria-expanded="false"
                                    data-san-product-search
                                    data-san-product-code
                                    data-selected-value="{{ $productCode }}"
                                >
                                <div class="san-product-results" role="listbox" data-san-product-results></div>
                            </div>
                        </td>
                        <td class="san-product-cell san-product-name-cell">
                            <div class="san-product-lookup">
                                <input
                                    type="text"
                                    name="lines[{{ $index }}][product_name]"
                                    value="{{ $line['product_name'] }}"
                                    class="form-control"
                                    placeholder="Type product name"
                                    autocomplete="off"
                                    spellcheck="false"
                                    required
                                    aria-autocomplete="list"
                                    aria-expanded="false"
                                    data-san-product-search
                                    data-san-product-name
                                    data-selected-value="{{ $line['product_name'] }}"
                                >
                                <div class="san-product-results" role="listbox" data-san-product-results></div>
                            </div>
                        </td>
                        <td class="san-batch-cell">
                            <input
                                type="hidden"
                                name="lines[{{ $index }}][expiry_date]"
                                value="{{ $line['expiry_date'] }}"
                                data-san-expiry-date
                            >
                            <select
                                name="lines[{{ $index }}][batch_no]"
                                class="form-control"
                                data-san-batch-no
                                data-selected-value="{{ $line['batch_no'] }}"
                                disabled
                            >
                                @if ($line['batch_no'] !== '')
                                    <option value="{{ $line['batch_no'] }}" selected>{{ $line['batch_no'] }}</option>
                                @else
                                    <option value="">Select product first</option>
                                @endif
                            </select>
                            <small class="san-batch-help" data-san-batch-help>
                                Only batches with available stock are shown. {{ (bool) ($settings['require_batch_when_available'] ?? true) ? 'Selection is required when batches exist.' : 'Selection is optional.' }}
                            </small>
                        </td>
                        <td class="san-line-adjustment-type-cell">
                            <select
                                name="lines[{{ $index }}][stock_adjustment_type]"
                                class="form-control"
                                data-san-line-adjustment-type
                                required
                            >
                                @foreach (['increase' => 'Increase', 'decrease' => 'Decrease'] as $directionValue => $directionLabel)
                                    <option value="{{ $directionValue }}" @selected(($line['stock_adjustment_type'] ?? 'increase') === $directionValue)>
                                        {{ $directionLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input
                                name="lines[{{ $index }}][system_qty]"
                                type="number"
                                step="any"
                                value="{{ $line['system_qty'] }}"
                                class="form-control"
                                data-san-system-qty
                                readonly
                            >
                        </td>
                        <td>
                            <input
                                name="lines[{{ $index }}][counted_qty]"
                                type="number"
                                step="any"
                                value="{{ $line['counted_qty'] }}"
                                class="form-control"
                                data-san-counted-qty
                                required
                                @if(! (bool) ($settings['allow_negative_stock'] ?? false)) min="0" @endif
                            >
                        </td>
                        <td>
                            <input
                                name="lines[{{ $index }}][unit_cost]"
                                type="number"
                                step="any"
                                min="0"
                                value="{{ $line['unit_cost'] }}"
                                class="form-control"
                                data-san-unit-cost
                            >
                        </td>
                        <td class="san-line-action-column">
                            <button type="button" class="btn btn-danger san-remove-line" data-san-remove-line title="Remove line">
                                &times;
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="san-form-actions">
        <button type="button" class="btn btn-info" data-san-add-line>Add Line</button>
        <button type="submit" class="btn btn-success">Save Draft</button>
    </div>
</form>
@endsection
