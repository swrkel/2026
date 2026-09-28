@extends('distribution::layouts.app')

@section('title', 'Create Product Loading')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* Distribution Loading page redesign only - no global CSS changes */
        .distribution-loading-page {
            padding: 18px 18px 70px;
        }

        .dl-header-card,
        .dl-card,
        .dl-product-card,
        .dl-table-card,
        .dl-footer-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e5edf6;
            box-shadow: 0 14px 35px rgba(28, 55, 90, .08);
        }

        .dl-header-card {
            padding: 24px 28px;
            margin-bottom: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .dl-business-title {
            font-size: 26px;
            font-weight: 700;
            margin: 0 0 8px;
            color: #1f3348;
        }

        .dl-business-meta {
            margin: 0;
            color: #63758a;
            font-size: 15px;
            line-height: 1.7;
        }

        .dl-number-box {
            min-width: 210px;
            padding: 20px 26px;
            border-radius: 18px;
            text-align: center;
            color: #fff;
            background: linear-gradient(135deg, #287eea 0%, #17b7d2 100%);
            box-shadow: 0 14px 30px rgba(23, 128, 222, .25);
        }

        .dl-number-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .8px;
            opacity: .95;
        }

        .dl-number-value {
            font-size: 28px;
            font-weight: 800;
            margin-top: 6px;
            line-height: 1;
        }

        .dl-card {
            padding: 24px;
            margin-bottom: 22px;
            min-height: 100%;
        }

        .dl-card-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f3348;
            margin: 0 0 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid #e8eef6;
        }

        .dl-form-row {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
        }

        .dl-form-row label {
            width: 34%;
            margin: 0;
            text-align: right;
            padding-right: 18px;
            font-weight: 600;
            color: #2e4157;
        }

        .dl-form-row .dl-control {
            width: 66%;
        }

        .distribution-loading-page .form-control,
        .distribution-loading-page .select2-container .select2-selection--single {
            min-height: 46px;
            border-radius: 11px !important;
            border: 1px solid #d9e4ef !important;
            box-shadow: none !important;
            font-size: 14px;
        }

        .distribution-loading-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 44px;
            padding-left: 14px;
        }

        .distribution-loading-page .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px;
        }

        .dl-product-card {
            padding: 22px;
            margin: 8px 0 22px;
        }

        .dl-product-filter-row {
            display: flex;
            gap: 16px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .dl-product-filter-row .dl-filter-group {
            flex: 1 1 220px;
        }

        .dl-product-filter-row label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2e4157;
        }

        .dl-add-product-wrap {
            display: flex;
            gap: 10px;
        }

        .dl-add-product-wrap .select2-container,
        .dl-add-product-wrap select {
            flex: 1;
        }

        .dl-table-card {
            padding: 20px;
            margin-bottom: 22px;
        }

        .dl-table-card .table-responsive {
            overflow-x: auto;
        }

        table.loading {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        table.loading th,
        table.loading td {
            border: 1px solid #d6dee8;
            padding: 9px 8px;
            font-size: 13px;
            vertical-align: middle;
            text-align: center;
        }

        table.loading th {
            background: #eef3f8;
            color: #1f3348;
            font-weight: 700;
            white-space: nowrap;
        }

        table.loading td.text-right,
        table.loading th.text-right {
            text-align: right !important;
        }

        table.loading td input[type="number"] {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .col-index { width: 50px; }
        .col-product { min-width: 260px; text-align: left !important; }
        .col-qty { min-width: 120px; }
        .col-price { min-width: 130px; }

        table.loading tfoot td {
            background: #f8fafc;
            font-weight: 700;
        }

        .dl-signature-card {
            margin-top: 22px;
            display: flex;
            gap: 30px;
            justify-content: space-between;
        }

        .dl-signature {
            flex: 1;
            text-align: center;
            color: #2e4157;
            font-weight: 600;
        }

        .dl-signature .line {
            border-top: 1px dashed #9aa9ba;
            margin: 42px 20px 8px;
        }

        .dl-footer-card {
            padding: 18px 22px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            position: sticky;
            bottom: 0;
            z-index: 5;
        }

        .dl-btn-primary,
        .dl-btn-secondary {
            border: 0;
            border-radius: 11px;
            padding: 12px 24px;
            font-weight: 700;
        }

        .dl-btn-primary {
            color: #fff;
            background: linear-gradient(135deg, #3180c2 0%, #276aa4 100%);
        }

        .dl-btn-secondary {
            color: #1f3348;
            background: #fff;
            border: 1px solid #e3eaf3;
        }

        @media (max-width: 991px) {
            .dl-header-card { flex-direction: column; align-items: stretch; }
            .dl-number-box { min-width: 100%; }
            .dl-form-row { display: block; }
            .dl-form-row label { width: 100%; text-align: left; padding: 0 0 8px; }
            .dl-form-row .dl-control { width: 100%; }
            .dl-footer-card { position: static; flex-direction: column; }
            .dl-footer-card .btn { width: 100%; }
        }
    </style>

    <section class="content distribution-loading-page">
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Error!</strong> Please fix the following issues:
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="loading_form" method="POST" action="{{ route('distribution.loadings.store') }}">
            @csrf

            <div class="dl-header-card">
                <div>
                    <h2 class="dl-business-title">{{ request()->session()->get('business.name') ?? 'Business Location' }}</h2>
                    <p class="dl-business-meta"><i class="fa fa-truck"></i> Product Loading Sheet</p>
                    <p class="dl-business-meta">Prepare and save product loading details for sales rep and vehicle.</p>
                </div>
                <div class="dl-number-box">
                    <div class="dl-number-label">Loading Form No</div>
                    <div class="dl-number-value">{{ $loading_no }}</div>
                    <input type="hidden" name="loading_no" value="{{ $loading_no }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="dl-card">
                        <h3 class="dl-card-title"><i class="fa fa-user"></i> Sales Rep Information</h3>

                        <div class="dl-form-row">
                            <label>
                                @if (!empty($auto_date_time))
                                    Date & Time:
                                @else
                                    Date:
                                @endif
                            </label>
                            <div class="dl-control">
                                @if (!empty($show_date_picker))
                                    <input type="date" name="date" id="date" class="form-control" value="{{ date('Y-m-d') }}">
                                @elseif(!empty($auto_date_time))
                                    <input type="text" name="date" id="date" class="form-control" value="{{ date('Y-m-d H:i:s') }}" readonly>
                                @else
                                    <input type="date" name="date" id="date" class="form-control" value="{{ date('Y-m-d') }}">
                                @endif
                            </div>
                        </div>

                        <div class="dl-form-row">
                            <label>Sales Rep:</label>
                            <div class="dl-control">
                                <select name="sales_rep_id" id="sales_rep_id" class="form-control">
                                    @foreach ($salesReps as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="dl-card">
                        <h3 class="dl-card-title"><i class="fa fa-truck"></i> Loading & Vehicle Information</h3>

                        <div class="dl-form-row">
                            <label>Vehicle No:</label>
                            <div class="dl-control">
                                <select name="vehicle_id" id="vehicle_id" class="form-control">
                                    @foreach ($vehicles as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="dl-form-row">
                            <label>Product Category:</label>
                            <div class="dl-control">
                                <select id="category_id" name="product_category_id" class="form-control">
                                    <option value="all">All</option>
                                    @foreach ($categories as $id => $n)
                                        <option value="{{ $id }}">{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="dl-form-row">
                            <label>Product Sub Category:</label>
                            <div class="dl-control">
                                <select id="sub_category_id" class="form-control">
                                    <option value="all">All</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dl-product-card">
                <div class="dl-product-filter-row">
                    <div class="dl-filter-group" style="flex: 2 1 420px;">
                        <label>Select Product to Add</label>
                        <div class="dl-add-product-wrap">
                            <select id="product_select" class="form-control">
                                <option value="">-- Select Product --</option>
                            </select>
                            <button type="button" id="add_row" class="btn btn-success dl-btn-primary"><i class="fa fa-plus"></i> Add</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dl-table-card">
                <h3 class="dl-card-title"><i class="fa fa-list"></i> Product Loading List</h3>
                <div class="table-responsive">
                    <table class="loading" id="loading_table">
                        <thead>
                            <tr>
                                <th class="col-index">@lang('distribution::lang.index')</th>
                                <th class="col-product">@lang('distribution::lang.product')</th>
                                <th class="col-qty">@lang('distribution::lang.available_qty')</th>
                                <th class="col-qty">@lang('distribution::lang.vehicle_balance_qty')</th>
                                <th class="col-qty">@lang('distribution::lang.requested_qty')</th>
                                <th class="col-qty">@lang('distribution::lang.issued_qty')</th>
                                <th class="col-price">@lang('distribution::lang.unit_sale_price')</th>
                                <th class="col-price">@lang('distribution::lang.total_in_sale_price')</th>
                                <th class="col-index"><i class="fa fa-trash"></i></th>
                            </tr>
                        </thead>
                        <tbody id="loading_body"></tbody>
                        <tfoot>
                            <tr>
                                <td></td>
                                <td></td>
                                <td style="text-align:center"><strong>Total</strong></td>
                                <td id="total_vehicle_balance" style="text-align:right">0.0000</td>
                                <td id="total_requested" style="text-align:right">0.0000</td>
                                <td id="total_issued" style="text-align:right">0.0000</td>
                                <td></td>
                                <td id="total_sale_price" style="text-align:right">0.0000</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="dl-signature-card">
                    <div class="dl-signature">
                        <div class="line"></div>
                        <div>Store Rider Signature</div>
                    </div>
                    <div class="dl-signature">
                        <div class="line"></div>
                        <div>Sales Rep Signature</div>
                    </div>
                </div>
            </div>

            <div class="dl-footer-card">
                <a href="{{ route('distribution.loadings.index') }}" class="btn dl-btn-secondary"><i class="fa fa-arrow-left"></i> Back</a>
                <button type="submit" class="btn dl-btn-primary"><i class="fa fa-save"></i> Save Loading</button>
            </div>
        </form>
    </section>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            const category = document.getElementById('category_id');
            const subCategory = document.getElementById('sub_category_id');
            const productSelect = document.getElementById('product_select');
            const addRowBtn = document.getElementById('add_row');
            const loadingBody = document.getElementById('loading_body');
            const totalRequested = document.getElementById('total_requested');
            const totalSalePrice = document.getElementById('total_sale_price');
            const vehicleSelect = document.getElementById('vehicle_id');

            const CURRENCY_PRECISION = {{ session('business.currency_precision', 2) }};
            const QTY_PRECISION = {{ session('business.quantity_precision', 2) }};

            // REMOVE THIS DATATABLE INITIALIZATION - IT'S CAUSING THE ISSUE
            // let table = $('#loading_table').DataTable({
            //     processing: true,
            //     serverSide: false,
            // })

            // Instead, just initialize Select2
            $('#category_id').select2({
                width: '100%',
                placeholder: 'All',
                allowClear: true,
                minimumResultsForSearch: 1
            });

            // Initialize Select2 for Product Sub Category with search
            $('#sub_category_id').select2({
                width: '100%',
                placeholder: 'All',
                allowClear: true,
                minimumResultsForSearch: 1
            });

            // Initialize Select2 for Sales Rep dropdown with search
            $('#sales_rep_id').select2({
                width: '100%',
                placeholder: '-- Select --',
                allowClear: true,
                minimumInputLength: 0
            });

            // Initialize Select2 for Vehicle dropdown with search
            $('#vehicle_id').select2({
                width: '100%',
                placeholder: '-- Select --',
                allowClear: true,
                minimumInputLength: 0
            });

            // Initialize Select2 only for product select
            $('#product_select').select2({
                width: '100%',
                placeholder: '-- Select Product --',
                allowClear: true
            });

            // helper to fetch json
            const fetchJSON = (url) => fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(r => r.json());

            // load subcategories & products when category selected
            $('#category_id').on('change', function() {
                const cat = $(this).val() || '';

                // Reset subcategory dropdown
                $('#sub_category_id').select2('destroy');
                $('#sub_category_id').empty().append('<option value="all">All</option>');

                if (!cat || cat === 'all') {
                    $('#sub_category_id').select2({
                        width: '100%',
                        placeholder: 'All',
                        allowClear: true,
                        minimumResultsForSearch: 1
                    });
                    loadProducts('all', 'all');
                    return;
                }

                // Load subcategories for selected category
                const subUrl = '{{ route('distribution.loadings.subcategories') }}?category_id=' + cat;
                fetchJSON(subUrl)
                    .then(subs => {
                        $('#sub_category_id').empty().append('<option value="all">All</option>');

                        if (subs && subs.length > 0) {
                            subs.forEach(sc => {
                                $('#sub_category_id').append(
                                    $('<option>', {
                                        value: sc.id,
                                        text: sc.name
                                    })
                                );
                            });
                        }

                        // Reinitialize Select2 with search
                        $('#sub_category_id').select2({
                            width: '100%',
                            placeholder: 'All',
                            allowClear: true,
                            minimumResultsForSearch: 1
                        });

                        $('#sub_category_id').val('all').trigger('change');

                        // Load products after subcategories are loaded
                        loadProducts(cat, 'all');
                    })
                    .catch(err => {
                        $('#sub_category_id').select2({
                            width: '100%',
                            placeholder: 'All',
                            allowClear: true,
                            minimumResultsForSearch: 1
                        });
                        loadProducts(cat, 'all');
                    });
            });

            // Also reload products when vehicle changes
            $('#vehicle_id').on('change', function() {
                const cat = $('#category_id').val() || '';
                const sub = $('#sub_category_id').val() || 'all';
                if (cat || sub !== 'all') {
                    loadProducts(cat, sub);
                }
            });

            // Reload products when sub-category changes
            $('#sub_category_id').on('change', function() {
                const cat = $('#category_id').val() || '';
                const sub = $(this).val() || 'all';
                loadProducts(cat, sub);
            });

            function loadProducts(cat, sub) {
                // Normalize: if sub is 'all' or empty, pass empty string to backend
                const subParam = (sub === 'all' || !sub) ? '' : sub;
                const catParam = (cat === 'all' || !cat) ? '' : cat;

                const params = new URLSearchParams({
                    category_id: catParam,
                    sub_category_id: subParam,
                    vehicle_id: vehicleSelect.value || ''
                });
                const url = '{{ route('distribution.loadings.products') }}?' + params.toString();

                fetchJSON(url)
                    .then(response => {
                        const products = Array.isArray(response) ? response : [];

                        // Destroy Select2 before updating options
                        $('#product_select').select2('destroy');

                        productSelect.innerHTML = '<option value="">-- Select Product --</option>';

                        if (products && products.length > 0) {
                            products.forEach(p => {
                                const o = document.createElement('option');
                                o.value = p.product_id;
                                o.textContent = p.name + ' (Av: ' + (p.available_qty || 0) + ')';
                                o.dataset.availableQty = p.available_qty || 0;
                                o.dataset.salePrice = p.sale_price || 0;
                                productSelect.appendChild(o);
                            });
                        } else {
                            const o = document.createElement('option');
                            o.value = '';
                            o.textContent = 'No products available';
                            o.disabled = true;
                            productSelect.appendChild(o);
                        }

                        // Reinitialize Select2
                        $('#product_select').select2({
                            width: '100%',
                            placeholder: '-- Select Product --',
                            allowClear: true
                        });
                    })
                    .catch(err => {
                        console.error('Error loading products:', err);
                    });
            }

            function formatWithCommasFixed(value, decimals) {
                if (isNaN(value) || value === '') return '';
                const parts = parseFloat(value).toFixed(decimals).split(".");
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                return parts.join(".");
            }

            // Add empty row message function
            function showEmptyMessage() {
                if (loadingBody.children.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.id = 'empty_message_row';
                    emptyRow.innerHTML = '<td colspan="9" style="text-align:center; padding:30px;">No products added yet. Select a product and click "Add" to continue.</td>';
                    loadingBody.appendChild(emptyRow);
                } else {
                    const emptyRow = document.getElementById('empty_message_row');
                    if (emptyRow) emptyRow.remove();
                }
            }

            // when clicked Add, fetch vehicle stock and append row with inputs
            addRowBtn.addEventListener('click', function() {
                const pid = productSelect.value;
                if (!pid) {
                    alert('Select product');
                    return;
                }
                
                // Check if product already exists
                let productExists = false;
                const existingProducts = document.querySelectorAll('input[name="product_id[]"]');
                existingProducts.forEach(input => {
                    if (input.value == pid) productExists = true;
                });
                
                if (productExists) {
                    alert('This product is already added to the list!');
                    return;
                }
                
                const selected = productSelect.options[productSelect.selectedIndex];
                const productName = selected.textContent.split(' (Av:')[0];
                const availableQtyRaw = parseFloat(selected.dataset.availableQty || 0);
                const salePriceRaw = parseFloat(selected.dataset.salePrice || 0);
                const availableQtyDisplay = formatWithCommasFixed(availableQtyRaw, QTY_PRECISION);
                const salePriceDisplay = formatWithCommasFixed(salePriceRaw, CURRENCY_PRECISION);
                const vehicleId = vehicleSelect.value;
                
                if (!vehicleId) {
                    alert('Please select a vehicle first');
                    return;
                }
                
                const url = '{{ route('distribution.loadings.vehicle_stock') }}?vehicle_id=' + vehicleId + '&product_id=' + pid;
                fetchJSON(url).then(resp => {
                    const vehicleBalanceRaw = parseFloat(resp.vehicle_balance_qty || 0);
                    const vehicleBalanceDisplay = formatWithCommasFixed(vehicleBalanceRaw, QTY_PRECISION);

                    const tr = document.createElement('tr');
                    const idx = loadingBody.children.length + 1;

                    tr.innerHTML = `
                        <td>${idx}</td>
                        <td style="text-align:left">
                            ${productName}
                            <input type="hidden" name="product_id[]" value="${pid}">
                            <input type="hidden" name="product_name[]" value="${productName}">
                        </td>
                        <td class="text-right">
                            ${availableQtyDisplay}
                            <input type="hidden" name="available_qty[]" value="${availableQtyRaw}">
                        </td>
                        <td class="text-right vehicle-balance">
                            ${vehicleBalanceDisplay}
                            <input type="hidden" name="vehicle_balance_qty[]" value="${vehicleBalanceRaw}">
                        </td>
                        <td class="text-right">
                            <input type="number" step="any" name="requested_qty[]" class="requested form-control requested-qty" value="0" style="width:100px; text-align:right;">
                        </td>
                        <td class="text-right">
                            <input type="number" step="any" name="issued_qty[]" class="issued form-control issued-qty" value="0" style="width:100px; text-align:right;">
                        </td>
                        <td class="text-right">
                            <input type="number" step="any" name="unit_sale_price[]" class="unit_price form-control unit-price" value="${salePriceRaw}" style="width:100px; text-align:right;">
                        </td>
                        <td class="text-right line_total">
                            <span class="line-total-span">0.00</span>
                            <input type="hidden" name="line_total[]" value="0">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-xs remove_row"><i class="fa fa-times"></i></button>
                        </td>
                    `;

                    // recalc line total on input
                    const issuedInput = tr.querySelector('.issued');
                    const unitPriceInput = tr.querySelector('.unit_price');
                    const lineTotalInput = tr.querySelector('input[name="line_total[]"]');
                    const lineTotalSpan = tr.querySelector('.line_total span');

                    function updateLineTotal() {
                        const issued = parseFloat(issuedInput.value) || 0;
                        const price = parseFloat(unitPriceInput.value) || 0;
                        const total = issued * price;
                        lineTotalInput.value = total.toFixed(CURRENCY_PRECISION);
                        if (lineTotalSpan) lineTotalSpan.textContent = formatWithCommasFixed(total, CURRENCY_PRECISION);
                        recalcFooter();
                    }

                    issuedInput.addEventListener('input', updateLineTotal);
                    unitPriceInput.addEventListener('input', updateLineTotal);

                    loadingBody.appendChild(tr);
                    showEmptyMessage();
                    recalcFooter();

                    // Remove row logic
                    tr.querySelector('.remove_row').addEventListener('click', function() {
                        tr.remove();
                        // Re-index rows
                        Array.from(loadingBody.children).forEach((row, index) => {
                            const firstCell = row.querySelector('td:first-child');
                            if (firstCell && row.id !== 'empty_message_row') {
                                firstCell.textContent = index + 1;
                            }
                        });
                        showEmptyMessage();
                        recalcFooter();
                    });
                });
            });

            function recalcFooter() {
                let totalVehicle = 0;
                let totalRequested = 0;
                let totalIssued = 0;
                let totalSale = 0;

                Array.from(loadingBody.children).forEach(tr => {
                    // Skip empty message row
                    if (tr.id === 'empty_message_row') return;
                    
                    const getVal = (name) => {
                        const el = tr.querySelector(`input[name="${name}[]"]`);
                        return el ? parseFloat(el.value) || 0 : 0;
                    };
                    
                    const vehicleBalanceEl = tr.querySelector('.vehicle-balance');
                    const vehicleBalance = vehicleBalanceEl ? parseFloat(vehicleBalanceEl.textContent.replace(/,/g, '')) || 0 : 0;

                    totalVehicle += vehicleBalance;
                    totalRequested += getVal('requested_qty');
                    totalIssued += getVal('issued_qty');
                    totalSale += getVal('line_total');
                });

                const totalVehicleEl = document.getElementById('total_vehicle_balance');
                const totalRequestedEl = document.getElementById('total_requested');
                const totalIssuedEl = document.getElementById('total_issued');
                const totalSalePriceEl = document.getElementById('total_sale_price');
                
                if (totalVehicleEl) totalVehicleEl.textContent = formatWithCommasFixed(totalVehicle, QTY_PRECISION);
                if (totalRequestedEl) totalRequestedEl.textContent = formatWithCommasFixed(totalRequested, QTY_PRECISION);
                if (totalIssuedEl) totalIssuedEl.textContent = formatWithCommasFixed(totalIssued, QTY_PRECISION);
                if (totalSalePriceEl) totalSalePriceEl.textContent = formatWithCommasFixed(totalSale, CURRENCY_PRECISION);
            }

            // initial load products (All)
            loadProducts('all', 'all');
            showEmptyMessage();

            // Form validation before submit
            document.getElementById('loading_form').addEventListener('submit', function(e) {
                const salesRep = document.getElementById('sales_rep_id').value;
                const vehicle = document.getElementById('vehicle_id').value;
                const rows = loadingBody.children.length;
                const hasEmptyRow = document.getElementById('empty_message_row') !== null;
                const actualRows = hasEmptyRow ? 0 : rows;

                if (!salesRep) {
                    e.preventDefault();
                    alert('Please select a Sales Rep');
                    document.getElementById('sales_rep_id').focus();
                    return false;
                }

                if (!vehicle) {
                    e.preventDefault();
                    alert('Please select a Vehicle');
                    document.getElementById('vehicle_id').focus();
                    return false;
                }

                if (actualRows === 0) {
                    e.preventDefault();
                    alert('Please add at least one product to the loading sheet');
                    return false;
                }

                // Check if any issued qty is entered
                let hasIssuedQty = false;
                Array.from(loadingBody.children).forEach(tr => {
                    if (tr.id === 'empty_message_row') return;
                    const issued = parseFloat(tr.querySelector('input[name="issued_qty[]"]')?.value) || 0;
                    if (issued > 0) hasIssuedQty = true;
                });

                if (!hasIssuedQty) {
                    e.preventDefault();
                    alert('Please enter at least one Issued Qty greater than 0');
                    return false;
                }

                return true;
            });
        });
    </script>
@endsection
