@extends('productsnew::layouts.app')

@section('productsnew_page_title', 'Stock Center')
@section('productsnew_page_subtitle', 'Available, low, zero and negative stock monitoring')

@push('css')
<style>
    .pn-stock-center-table-wrap { overflow-x: auto; }
    .pn-stock-center-table { min-width: 1080px; }
    .pn-stock-center-table th,
    .pn-stock-center-table td { vertical-align: middle !important; }
    .pn-stock-filter-toolbar { display: grid; grid-template-columns: repeat(6, minmax(150px, 1fr)); gap: 10px; align-items: end; margin-bottom: 16px; }
    .pn-stock-filter-field { min-width: 0; }
    .pn-stock-filter-field label { display: block; margin-bottom: 5px; color: #52647a; font-size: 12px; font-weight: 700; }
    .pn-stock-filter-toolbar .form-control,
    .pn-stock-filter-toolbar .select2-container { width: 100% !important; }
    .pn-stock-filter-toolbar .select2-selection--single { min-height: 34px; }
    .pn-stock-filter-links { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; grid-column: span 2; }
    @media (max-width: 1400px) { .pn-stock-filter-toolbar { grid-template-columns: repeat(4, minmax(150px, 1fr)); } }
    @media (max-width: 991px) { .pn-stock-filter-toolbar { grid-template-columns: repeat(2, minmax(150px, 1fr)); } }
    @media (max-width: 600px) { .pn-stock-filter-toolbar { grid-template-columns: 1fr; } .pn-stock-filter-links { grid-column: span 1; } }
    .pn-stock-details-btn { min-width: 82px; }
    .pn-stock-details-modal .modal-dialog { width: min(1080px, calc(100% - 30px)); }
    .pn-stock-details-modal .modal-content { border: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 55px rgba(22, 43, 72, .24); }
    .pn-stock-details-modal .modal-header { padding: 16px 20px; border-bottom: 1px solid #e5ebf3; background: #f8faff; }
    .pn-stock-details-modal .modal-title { color: #17283f; font-weight: 800; }
    .pn-stock-details-modal .modal-body { padding: 20px; background: #f5f8fc; max-height: calc(100vh - 180px); overflow-y: auto; }
    .pn-stock-details-modal .modal-footer { padding: 12px 20px; border-top: 1px solid #e5ebf3; background: #fff; }
    .pn-stock-summary { display: grid; grid-template-columns: minmax(0, 2fr) minmax(220px, 1fr); gap: 14px; margin-bottom: 16px; }
    .pn-stock-summary-card { padding: 15px 17px; border: 1px solid #dfe7f1; border-radius: 13px; background: #fff; }
    .pn-stock-summary-label { display: block; color: #758399; font-size: 12px; font-weight: 750; text-transform: uppercase; letter-spacing: .04em; }
    .pn-stock-summary-value { display: block; margin-top: 4px; color: #17283f; font-size: 18px; font-weight: 850; }
    .pn-stock-summary-value.pn-qty { color: #1b7f5a; font-size: 24px; text-align: right; }
    .pn-stock-detail-section { margin-bottom: 16px; padding: 16px; border: 1px solid #dfe7f1; border-radius: 14px; background: #fff; }
    .pn-stock-detail-section:last-child { margin-bottom: 0; }
    .pn-stock-detail-section h4 { margin: 0 0 12px; color: #20354f; font-size: 15px; font-weight: 850; }
    .pn-stock-detail-section .table-responsive { margin: 0; border: 0; }
    .pn-stock-detail-section table { margin-bottom: 0; }
    .pn-stock-detail-section thead th { background: #f4f7fb; color: #405168; font-size: 12px; text-transform: uppercase; letter-spacing: .025em; }
    .pn-stock-details-loading,
    .pn-stock-details-error,
    .pn-stock-details-empty { padding: 30px 16px; text-align: center; }
    .pn-stock-details-error { color: #b42318; }
    .pn-stock-details-empty { color: #718096; }
    .pn-stock-details-modal.pn-fallback-open { display: block; opacity: 1; background: rgba(0, 0, 0, .5); }
    .pn-stock-details-modal.pn-fallback-open .modal-dialog { transform: none; }
    @media (max-width: 767px) {
        .pn-stock-summary { grid-template-columns: 1fr; }
        .pn-stock-summary-value.pn-qty { text-align: left; }
        .pn-stock-details-modal .modal-dialog { width: calc(100% - 16px); margin: 8px; }
    }
</style>
@endpush

@section('productsnew_content')
<div class="pn-card">
    <div class="pn-card-header">
        <strong>Stock Center</strong>
        <span class="pn-muted">Available, low, zero and negative stock monitoring</span>
    </div>

    <div class="pn-card-body">
        @php
            $stockLocations = $filterLookups['locations'] ?? collect();
            $stockCategories = $filterLookups['categories'] ?? collect();
            $stockSubCategories = $filterLookups['sub_categories'] ?? collect();
            $stockProducts = $filterLookups['products'] ?? collect();
        @endphp
        <form class="pn-stock-filter-toolbar" method="get" action="{{ route('products-new.stock-center.index') }}" data-pn-stock-auto-filter-form>
            <input type="hidden" name="from_date" id="pn_stock_from_date" value="{{ $filters['from_date'] }}">
            <input type="hidden" name="to_date" id="pn_stock_to_date" value="{{ $filters['to_date'] }}">

            <div class="pn-stock-filter-field">
                <label for="pn_stock_location">Location</label>
                <select class="form-control pn-stock-searchable" id="pn_stock_location" name="location_id" data-placeholder="Select Location" data-pn-stock-auto-filter>
                    {{-- All Locations, for those permitted to see them. --}}
                    @if (auth()->user()->can('access_all_locations') || auth()->user()->can('superadmin') || auth()->user()->hasRole('Admin#' . session('user.business_id')))
                        <option value="all" @selected(($filters['location_id'] ?? '') === 'all')>All Locations</option>
                    @endif
                    @foreach($filterLookups["locations"] ?? [] as $location)
                        <option value="{{ $location->id }}" @selected((int)($filters['location_id'] ?? 0) === (int)$location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pn-stock-filter-field">
                <label for="pn_stock_date_range">Date Range</label>
                <input type="text" id="pn_stock_date_range" class="form-control" readonly
                       value="{{ date('d/m/Y', strtotime($filters['from_date'])) }} ~ {{ date('d/m/Y', strtotime($filters['to_date'])) }}"
                       placeholder="Select Date Range">
            </div>

            <div class="pn-stock-filter-field">
                <label for="pn_stock_category">Product Category</label>
                <select class="form-control pn-stock-searchable" id="pn_stock_category" name="category_id" data-placeholder="All Categories" data-pn-stock-category data-pn-stock-auto-filter>
                    <option value="">All Categories</option>
                    @foreach($stockCategories as $category)
                        <option value="{{ $category->id }}" @selected((int)($filters['category_id'] ?? 0) === (int)$category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pn-stock-filter-field">
                <label for="pn_stock_sub_category">Product Sub Category</label>
                <select class="form-control pn-stock-searchable" id="pn_stock_sub_category" name="sub_category_id" data-placeholder="All Sub Categories" data-pn-stock-sub-category data-pn-stock-auto-filter>
                    <option value="">All Sub Categories</option>
                    @foreach($stockSubCategories as $subCategory)
                        <option value="{{ $subCategory->id }}" @selected((int)($filters['sub_category_id'] ?? 0) === (int)$subCategory->id)>{{ $subCategory->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pn-stock-filter-field">
                <label for="pn_stock_product">Product</label>
                <select class="form-control pn-stock-searchable" id="pn_stock_product" name="product_id" data-placeholder="All Products" data-pn-stock-product data-pn-stock-auto-filter>
                    <option value="">All Products</option>
                    @foreach($stockProducts as $product)
                        <option value="{{ $product->id }}" @selected((int)($filters['product_id'] ?? 0) === (int)$product->id)>
                            {{ $product->name }}{{ !empty($product->sku) ? ' — '.$product->sku : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pn-stock-filter-field">
                <label for="pn_stock_search">Search</label>
                <input class="form-control" id="pn_stock_search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Product or SKU" data-pn-stock-auto-search>
            </div>

            <div class="pn-stock-filter-field">
                <label for="pn_stock_status">Stock Status</label>
                <select class="form-control" id="pn_stock_status" name="stock_status" data-pn-stock-auto-filter>
                    <option value="">All Stock</option>
                    <option value="low" @selected(($filters['stock_status'] ?? '') === 'low')>Low Stock</option>
                    <option value="zero" @selected(($filters['stock_status'] ?? '') === 'zero')>Zero Stock</option>
                    <option value="negative" @selected(($filters['stock_status'] ?? '') === 'negative')>Negative Stock</option>
                </select>
            </div>

            <div class="pn-stock-filter-links">
                <a class="pn-btn pn-btn-light" href="{{ route('products-new.stock-center.index') }}" title="Reset Filters"><i class="fa fa-refresh"></i> Reset</a>
                <a class="pn-btn pn-btn-success" href="{{ route('products-new.inventory-movements.index') }}">Inventory Movements</a>
                <a class="pn-btn pn-btn-warning" href="{{ route('products-new.opening-stock.index') }}">Opening Stock</a>
            </div>
        </form>

        <div class="pn-stock-center-table-wrap">
            <table class="table pn-table pn-stock-center-table">
                <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Variation</th>
                    <th>Location</th>
                    <th class="text-right">Available</th>
                    <th class="text-center">Batch No</th>
                    <th class="text-right">Products New Movement</th>
                    <th class="text-right">Alert</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    @php
                        $available = (float) $row->qty_available;
                        $alert = (float) $row->alert_quantity;
                        $detailsUrl = route('products-new.stock-center.details', ['product' => $row->product_id]);
                        if (!empty($row->details_variation_id)) {
                            $detailsUrl .= '?variation_id=' . urlencode((string) $row->details_variation_id);
                        }
                    @endphp
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->sku }}</td>
                        <td>{{ $row->variation_name }}</td>
                        <td>{{ $row->location_name }}</td>
                        <td class="text-right">{{ number_format($available, 3) }}</td>
                        <td class="text-center">
                            <button
                                type="button"
                                class="pn-btn pn-btn-info pn-btn-sm pn-stock-details-btn"
                                data-pn-stock-details-url="{{ $detailsUrl }}"
                            >
                                <i class="fa fa-list-alt" aria-hidden="true"></i>
                                Details
                            </button>
                        </td>
                        <td class="text-right">{{ number_format((float) $row->products_new_movement_qty, 3) }}</td>
                        <td class="text-right">{{ number_format($alert, 3) }}</td>
                        <td>
                            @if($available < 0)
                                <span class="pn-badge pn-danger">Negative</span>
                            @elseif($available == 0)
                                <span class="pn-badge pn-warning">Zero</span>
                            @elseif($alert > 0 && $available <= $alert)
                                <span class="pn-badge pn-warning">Low</span>
                            @else
                                <span class="pn-badge pn-success">OK</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center pn-muted">No stock records found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $rows->links() }}
    </div>
</div>

<div class="modal fade pn-stock-details-modal" id="pn-stock-details-modal" tabindex="-1" role="dialog" aria-labelledby="pn-stock-details-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" data-pn-modal-close>
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="pn-stock-details-title">Batch and Location Details</h4>
            </div>
            <div class="modal-body">
                <div id="pn-stock-details-loading" class="pn-stock-details-loading">
                    <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                    Loading stock details…
                </div>

                <div id="pn-stock-details-error" class="pn-stock-details-error" hidden></div>

                <div id="pn-stock-details-content" hidden>
                    <div class="pn-stock-summary">
                        <div class="pn-stock-summary-card">
                            <span class="pn-stock-summary-label">Product Name</span>
                            <span class="pn-stock-summary-value" id="pn-stock-product-name">—</span>
                        </div>
                        <div class="pn-stock-summary-card">
                            <span class="pn-stock-summary-label">Total Available Qty in All Locations</span>
                            <span class="pn-stock-summary-value pn-qty" id="pn-stock-total-qty">0.000</span>
                        </div>
                    </div>

                    <section class="pn-stock-detail-section">
                        <h4>Location, Batch and Store Availability</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>Location</th>
                                    <th>Batch No</th>
                                    <th class="text-right">Qty</th>
                                    <th>Stores</th>
                                </tr>
                                </thead>
                                <tbody id="pn-stock-location-rows"></tbody>
                            </table>
                        </div>
                    </section>

                    <section class="pn-stock-detail-section">
                        <h4>Batch Nos with the Locations</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>Batch No</th>
                                    <th class="text-right">Qty</th>
                                    <th>Location</th>
                                </tr>
                                </thead>
                                <tbody id="pn-stock-batch-rows"></tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="pn-btn pn-btn-light" data-dismiss="modal" data-pn-modal-close>Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('javascript')
<script>
(function (window, document) {
    'use strict';

    var filterForm = document.querySelector('[data-pn-stock-auto-filter-form]');
    if (filterForm && filterForm.dataset.pnAutoFilterReady !== '1') {
        filterForm.dataset.pnAutoFilterReady = '1';
        var filterTimer = null;
        var filterSubmitting = false;
        var category = filterForm.querySelector('[data-pn-stock-category]');
        var subCategory = filterForm.querySelector('[data-pn-stock-sub-category]');
        var product = filterForm.querySelector('[data-pn-stock-product]');

        function submitFilters(delay) {
            if (filterSubmitting) return;
            if (filterTimer) window.clearTimeout(filterTimer);
            filterTimer = window.setTimeout(function () {
                filterSubmitting = true;
                filterForm.submit();
            }, delay || 0);
        }

        if (window.jQuery && jQuery.fn.select2) {
            jQuery(filterForm).find('.pn-stock-searchable').each(function () {
                var $el = jQuery(this);
                if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
                $el.select2({
                    width: '100%',
                    allowClear: this.id !== 'pn_stock_location',
                    placeholder: $el.data('placeholder') || 'Select',
                    minimumResultsForSearch: 0
                });
            });
        }

        function handleAutoFilterChange(field) {
            if (field === category) {
                if (subCategory) {
                    subCategory.value = '';
                    if (window.jQuery && jQuery.fn.select2 && jQuery(subCategory).hasClass('select2-hidden-accessible')) {
                        // Refresh only the Select2 display.  The namespaced event
                        // does not call this auto-filter handler a second time.
                        jQuery(subCategory).val(null).trigger('change.select2');
                    }
                }
                if (product) {
                    product.value = '';
                    if (window.jQuery && jQuery.fn.select2 && jQuery(product).hasClass('select2-hidden-accessible')) {
                        jQuery(product).val(null).trigger('change.select2');
                    }
                }
            } else if (field === subCategory && product) {
                product.value = '';
                if (window.jQuery && jQuery.fn.select2 && jQuery(product).hasClass('select2-hidden-accessible')) {
                    jQuery(product).val(null).trigger('change.select2');
                }
            }

            submitFilters(50);
        }

        var autoFilterFields = filterForm.querySelectorAll('[data-pn-stock-auto-filter]');
        if (window.jQuery) {
            // Select2 emits jQuery change events.  Using a jQuery listener here
            // makes Category, Sub Category and Product selections submit
            // reliably instead of depending on a native DOM change event that
            // Select2 does not consistently dispatch.
            jQuery(filterForm).find('[data-pn-stock-auto-filter]')
                .off('change.pnStockAutoFilter')
                .on('change.pnStockAutoFilter', function () {
                    handleAutoFilterChange(this);
                });
        } else {
            autoFilterFields.forEach(function (field) {
                field.addEventListener('change', function () {
                    handleAutoFilterChange(field);
                });
            });
        }

        filterForm.querySelectorAll('[data-pn-stock-auto-search]').forEach(function (field) {
            field.addEventListener('input', function () { submitFilters(350); });
        });

        if (window.jQuery && window.moment && jQuery.fn.daterangepicker) {
            var $range = jQuery('#pn_stock_date_range');
            var start = moment(jQuery('#pn_stock_from_date').val(), 'YYYY-MM-DD');
            var end = moment(jQuery('#pn_stock_to_date').val(), 'YYYY-MM-DD');
            var settings = window.dateRangeSettings || {
                startDate: start,
                endDate: end,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            };
            settings.startDate = start;
            settings.endDate = end;
            $range.daterangepicker(settings, function (s, e) {
                jQuery('#pn_stock_from_date').val(s.format('YYYY-MM-DD'));
                jQuery('#pn_stock_to_date').val(e.format('YYYY-MM-DD'));
                $range.val(s.format(window.moment_date_format || 'DD/MM/YYYY') + ' ~ ' + e.format(window.moment_date_format || 'DD/MM/YYYY'));
                submitFilters(50);
            });
            $range.data('daterangepicker').setStartDate(start);
            $range.data('daterangepicker').setEndDate(end);
        }
    }

    var modal = document.getElementById('pn-stock-details-modal');
    if (!modal) return;

    var loading = document.getElementById('pn-stock-details-loading');
    var errorBox = document.getElementById('pn-stock-details-error');
    var content = document.getElementById('pn-stock-details-content');
    var productName = document.getElementById('pn-stock-product-name');
    var totalQty = document.getElementById('pn-stock-total-qty');
    var locationRows = document.getElementById('pn-stock-location-rows');
    var batchRows = document.getElementById('pn-stock-batch-rows');

    function formatQty(value) {
        var number = Number(value || 0);
        return Number.isFinite(number)
            ? number.toLocaleString(undefined, { minimumFractionDigits: 3, maximumFractionDigits: 3 })
            : '0.000';
    }

    function cell(text, className) {
        var td = document.createElement('td');
        td.textContent = text == null || text === '' ? '—' : String(text);
        if (className) td.className = className;
        return td;
    }

    function emptyRow(tbody, colspan, message) {
        var tr = document.createElement('tr');
        var td = cell(message, 'text-center pn-muted');
        td.colSpan = colspan;
        tr.appendChild(td);
        tbody.appendChild(tr);
    }

    function resetModal() {
        loading.hidden = false;
        errorBox.hidden = true;
        errorBox.textContent = '';
        content.hidden = true;
        productName.textContent = '—';
        totalQty.textContent = '0.000';
        locationRows.innerHTML = '';
        batchRows.innerHTML = '';
    }

    function openModal() {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
            window.jQuery(modal).modal('show');
            return;
        }

        modal.classList.add('in', 'pn-fallback-open');
        modal.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
            window.jQuery(modal).modal('hide');
            return;
        }

        modal.classList.remove('in', 'pn-fallback-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    function render(data) {
        var product = data.product || {};
        var nameParts = [product.name || '—'];
        if (product.variation_name) nameParts.push(product.variation_name);
        if (product.sku) nameParts.push('SKU: ' + product.sku);

        productName.textContent = nameParts.join(' / ');
        totalQty.textContent = formatQty(data.total_available_qty);

        var locations = Array.isArray(data.location_rows) ? data.location_rows : [];
        if (!locations.length) {
            emptyRow(locationRows, 4, 'No location, batch or store records found.');
        } else {
            locations.forEach(function (row) {
                var tr = document.createElement('tr');
                tr.appendChild(cell(row.location_name));
                tr.appendChild(cell(row.batch_no));
                tr.appendChild(cell(formatQty(row.qty), 'text-right'));
                tr.appendChild(cell(row.stores));
                locationRows.appendChild(tr);
            });
        }

        var batches = Array.isArray(data.batch_rows) ? data.batch_rows : [];
        if (!batches.length) {
            emptyRow(batchRows, 3, 'No batch records are available for this product.');
        } else {
            batches.forEach(function (row) {
                var tr = document.createElement('tr');
                tr.appendChild(cell(row.batch_no));
                tr.appendChild(cell(formatQty(row.qty), 'text-right'));
                tr.appendChild(cell(row.location_name));
                batchRows.appendChild(tr);
            });
        }

        loading.hidden = true;
        content.hidden = false;
    }

    function showError(message) {
        loading.hidden = true;
        content.hidden = true;
        errorBox.textContent = message || 'Unable to load the stock details.';
        errorBox.hidden = false;
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-pn-stock-details-url]');
        if (!button) return;

        event.preventDefault();
        resetModal();
        openModal();

        fetch(button.getAttribute('data-pn-stock-details-url'), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (body) {
                    if (!response.ok) {
                        throw new Error(body.message || 'Unable to load the stock details.');
                    }
                    return body;
                });
            })
            .then(render)
            .catch(function (error) { showError(error.message); });
    });

    modal.querySelectorAll('[data-pn-modal-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!(window.jQuery && window.jQuery.fn && window.jQuery.fn.modal)) {
                closeModal();
            }
        });
    });
})(window, document);
</script>
@endpush
