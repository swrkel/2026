@extends('distribution::layouts.app')

@section('title', 'Add Distribution Invoice')

@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('status'))
    <div class="alert alert-success">
        {{ is_array(session('status')) ? session('status')['msg'] : session('status') }}
    </div>
@endif

<style>
    .dis-invoice-page{background:#f5f7fb;padding-bottom:30px;}
    .erp-page-header{background:#fff;border:1px solid #e6e9f0;border-radius:10px;padding:18px 22px;margin-bottom:18px;box-shadow:0 2px 8px rgba(31,45,61,.06);}
    .erp-page-title{font-size:22px;font-weight:700;color:#1f2d3d;margin:0;line-height:1.3;}
    .erp-page-subtitle{font-size:12px;color:#7b8794;margin-top:4px;}
    .erp-page-actions{text-align:right;margin-top:3px;}
    .erp-page-badge{display:inline-block;background:#edf4ff;color:#1f6ed4;border:1px solid #d8e8ff;border-radius:18px;padding:6px 14px;font-weight:700;}
    .erp-card{background:#fff;border:1px solid #e6e9f0;border-radius:10px;margin-bottom:16px;box-shadow:0 2px 8px rgba(31,45,61,.05);overflow:visible;}
    .erp-card-header{padding:13px 18px;border-bottom:1px solid #edf0f5;background:#fbfcfe;display:flex;align-items:center;justify-content:space-between;gap:10px;}
    .erp-card-title{font-size:15px;font-weight:700;color:#263238;margin:0;}
    .erp-card-title i{color:#337ab7;margin-right:6px;}
    .erp-card-body{padding:18px;overflow:visible;}
    .erp-form-label{font-size:12px;font-weight:700;color:#4b5563;margin-bottom:6px;display:block;}
    .erp-form-label .required{color:#dd4b39;}
    .erp-card .form-control,.erp-card .select2-container--default .select2-selection--single{min-height:34px;border-radius:6px;border-color:#d7dde6;}
    .erp-card textarea.form-control{min-height:70px;}
    .erp-readonly{background:#f8fafc!important;color:#344054!important;font-weight:600;}
    .erp-business-box{border-left:4px solid #337ab7;background:#f8fbff;padding:12px 15px;border-radius:8px;margin-bottom:14px;}
    .erp-business-name{font-size:17px;font-weight:700;color:#1f2d3d;margin-bottom:5px;}
    .product-entry-grid{display:grid;grid-template-columns:2.2fr 1fr .8fr 1fr 1fr 1fr .9fr .9fr auto;gap:10px;align-items:end;}
    .product-entry-grid .form-group{margin-bottom:0;}
    .product-extra-grid{display:grid;grid-template-columns:1fr .8fr .9fr auto;gap:10px;align-items:end;margin-top:12px;}
    .erp-table-wrap{width:100%;overflow-x:auto;border:1px solid #e6e9f0;border-radius:8px;margin-top:15px;}
    .invoice-table{width:100%;border-collapse:collapse;background:#fff;margin-bottom:0;}
    .invoice-table th{background:#f4f7fb;color:#344054;font-size:12px;font-weight:700;text-align:center;border:1px solid #e6e9f0;padding:9px 8px;white-space:nowrap;}
    .invoice-table td{border:1px solid #edf0f5;padding:8px;vertical-align:middle;font-size:12px;}
    .invoice-table tfoot td{background:#fbfcfe;font-weight:700;color:#1f2d3d;}
    .invoice-index{width:70px;text-align:center;}.invoice-qty{width:100px;text-align:center;}.invoice-product{min-width:220px;}.invoice-unitprice,.invoice-amount,.invoice-disc{text-align:right;min-width:120px;}
    .btn-remove-line{background:#dd4b39;color:#fff;border:none;border-radius:5px;padding:5px 8px;}
    .btn-remove-line:hover{background:#c23321;}
    .summary-card{background:#fbfcfe;border:1px solid #e6e9f0;border-radius:8px;padding:14px;margin-top:15px;}
    .summary-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px dashed #dbe1ea;font-size:13px;}
    .summary-row:last-child{border-bottom:0;font-size:16px;font-weight:800;color:#1f6ed4;}
    .payment-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;align-items:end;}
    .action-footer{position:sticky;bottom:0;z-index:10;background:#fff;border:1px solid #e6e9f0;border-radius:10px;padding:12px 18px;margin-top:18px;box-shadow:0 -2px 12px rgba(31,45,61,.08);display:flex;justify-content:space-between;align-items:center;gap:10px;}
    .action-footer .btn{min-width:110px;}
    .help-text{font-size:11px;color:#7b8794;margin-top:4px;}
    @media(max-width:1199px){.product-entry-grid{grid-template-columns:repeat(3,1fr);} .product-extra-grid{grid-template-columns:repeat(2,1fr);} .payment-grid{grid-template-columns:repeat(2,1fr);}}
    @media(max-width:767px){.erp-page-actions{text-align:left;margin-top:12px}.product-entry-grid,.product-extra-grid,.payment-grid{grid-template-columns:1fr}.action-footer{position:static;display:block}.action-footer .btn{width:100%;margin-top:6px}.erp-card-body{padding:14px}}
</style>

<section class="content dis-invoice-page">
    <form method="POST" action="{{ url('distribution/invoices/store') }}" id="invoice_form">
        @csrf
        <input type="hidden" name="sales_order_id" value="{{ optional($salesOrder)->id }}">
        <input type="hidden" name="loading_sheet_no" id="loading_sheet_no" value="">

        <div class="erp-page-header">
            <div class="row">
                <div class="col-md-8">
                    <h1 class="erp-page-title"><i class="fa fa-file-text-o"></i> Add Distribution Invoice</h1>
                    <div class="erp-page-subtitle">Create a distribution invoice with customer, product, payment and shipping details.</div>
                </div>
                <div class="col-md-4 erp-page-actions">
                    <span class="erp-page-badge">Invoice No: <span id="invoice_no">{{ $invoice_no ?? 'AUTO' }}</span></span>
                </div>
            </div>
        </div>

        <div class="erp-card">
            <div class="erp-card-header">
                <h3 class="erp-card-title"><i class="fa fa-building"></i> Business & Invoice Information</h3>
            </div>
            <div class="erp-card-body">
                <div class="erp-business-box">
                    <div class="erp-business-name">{{ $business->name ?? 'Business Name' }}</div>
                    <div><strong>Address:</strong>
                        @if ($location)
                            {{ $location->landmark ?? ($location->address_1 ?? '') }}
                            @if ($location->city || $location->state || $location->country)
                                {{ ', ' . implode(', ', array_filter([$location->city, $location->state, $location->country])) }}
                            @endif
                        @else
                            Address
                        @endif
                    </div>
                    <div><strong>Contact Number:</strong> {{ $location->mobile ?? ($location->alternate_number ?? '') }}</div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="erp-form-label">Invoice Date & Time <span class="required">*</span></label>
                            <input type="datetime-local" name="date" id="invoice_date" class="form-control" value="{{ now()->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="erp-form-label">Delivery Date</label>
                            <input type="date" name="delivery_date" class="form-control" value="{{ optional($salesOrder)->delivery_date }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="erp-form-label">Shipping Status</label>
                            <select name="shipping_status" class="form-control">
                                @foreach (['ordered' => 'Ordered', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $shipping_key => $shipping_label)
                                    <option value="{{ $shipping_key }}" {{ optional($salesOrder)->shipping_status === $shipping_key ? 'selected' : '' }}>{{ $shipping_label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="erp-form-label">Loading Sheet No</label>
                            <input type="text" class="form-control erp-readonly" id="loading_sheet_info" value="--" readonly>
                            <div class="help-text">Auto-loaded by vehicle/date/category when available.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="erp-card">
            <div class="erp-card-header">
                <h3 class="erp-card-title"><i class="fa fa-user"></i> Customer & Delivery Information</h3>
            </div>
            <div class="erp-card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="erp-form-label">Customer <span class="required">*</span></label>
                            <div style="display:flex; gap:6px; align-items:center;">
                                <select name="customer_id" id="customer_id" class="form-control select2-search" required>
                                    <option value="">-- Select customer --</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer['id'] }}" data-address="{{ htmlspecialchars($customer['address'] ?? '', ENT_QUOTES, 'UTF-8') }}" data-phone="{{ htmlspecialchars($customer['phone'] ?? '', ENT_QUOTES, 'UTF-8') }}" {{ optional($salesOrder)->customer_id == $customer['id'] ? 'selected' : '' }}>{{ $customer['name'] }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-primary" id="add_new_customer_btn" title="Add customer"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="erp-form-label">Customer Address</label>
                            <input type="text" id="customer_address" class="form-control erp-readonly" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="erp-form-label">Customer Contact No</label>
                            <input type="text" id="customer_phone" class="form-control erp-readonly" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="erp-form-label">Sales Rep</label>
                            <select name="sales_rep_id" id="sales_rep_id" class="form-control select2-search">
                                <option value="">-- Select --</option>
                                @foreach ($salesReps as $id => $name)
                                    @php $selected_sales_rep = optional($salesOrder)->sales_rep_id ?? $default_sales_rep_id ?? null; @endphp
                                    <option value="{{ $id }}" {{ $selected_sales_rep == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="erp-form-label">Route</label>
                            <select name="route_id" id="route_id" class="form-control select2-search">
                                <option value="">-- Select --</option>
                                @foreach ($routes as $id => $name)
                                    <option value="{{ $id }}" {{ optional($salesOrder)->route_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="erp-form-label">Vehicle No</label>
                            <select name="vehicle_id" id="vehicle_id" class="form-control select2-search">
                                <option value="">-- Select --</option>
                                @foreach ($vehicles as $id => $name)
                                    <option value="{{ $id }}" {{ optional($salesOrder)->vehicle_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="erp-form-label">Product Category</label>
                            <select id="category_id" class="form-control select2-search" name="category_id">
                                <option value="">All</option>
                                @foreach ($categories as $id => $name)
                                    <option value="{{ $id }}" {{ optional($salesOrder)->category_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="erp-card">
            <div class="erp-card-header">
                <h3 class="erp-card-title"><i class="fa fa-cubes"></i> Product Entry</h3>
                <button type="button" id="add_line_top" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Add Product</button>
            </div>
            <div class="erp-card-body">
                <div class="product-entry-grid">
                    <div class="form-group">
                        <label class="erp-form-label">Search Products</label>
                        <select id="product_search" class="form-control select2-search"><option value="">Please Select</option></select>
                    </div>
                    <div class="form-group">
                        <label class="erp-form-label">Unit</label>
                        <select id="line_unit_id" class="form-control"></select>
                    </div>
                    <div class="form-group">
                        <label class="erp-form-label">Quantity</label>
                        <input id="line_qty" type="number" step="{{ '0.' . str_repeat('0', $quantity_precision - 1) . '1' }}" value="1" class="form-control" min="0">
                    </div>
                    <div class="form-group">
                        <label class="erp-form-label">Unit Price</label>
                        <input id="line_unit_price" type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" value="0" class="form-control erp-readonly" min="0" readonly>
                    </div>
                    <div class="form-group">
                        <label class="erp-form-label">Tax Type</label>
                        <select id="line_tax_id" class="form-control">
                            @if (!empty($taxes))
                                @foreach ($taxes as $tax_id => $tax_name)
                                    <option value="{{ $tax_id }}" @if (!empty($tax_attributes[$tax_id]['data-rate'])) data-rate="{{ $tax_attributes[$tax_id]['data-rate'] }}" @endif>{{ $tax_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="erp-form-label">Price Inc. Tax</label>
                        <input id="line_price_inc_tax" type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" value="0" class="form-control erp-readonly" min="0" readonly>
                    </div>
                    <div class="form-group">
                        <label class="erp-form-label">Discount Type</label>
                        <select id="line_discount_type" class="form-control"><option value="fixed">Fixed</option><option value="percentage">Percentage</option></select>
                    </div>
                    <div class="form-group">
                        <label class="erp-form-label">Discount</label>
                        <input id="line_discount" type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" value="0" class="form-control" min="0">
                    </div>
                    <div class="form-group">
                        <label class="erp-form-label">&nbsp;</label>
                        <button type="button" id="add_line" class="btn btn-success btn-block"><i class="fa fa-plus"></i> Add</button>
                    </div>
                </div>

                @php
                    $freeIssueToggle = !empty($module_enable['distribution_free']) && $module_enable['distribution_free'] === 'Yes';
                    $freeBottlesToggle = !empty($module_enable['distribution_free_bottles']) && $module_enable['distribution_free_bottles'] === 'Yes';
                @endphp
                <div class="product-extra-grid">
                    <div class="form-group">
                        <label class="erp-form-label">Total Price</label>
                        <input type="text" value="0.00" id="line_total_price_display" class="form-control erp-readonly" readonly>
                    </div>
                    @if ($freeIssueToggle)
                        <div class="form-group">
                            <label class="erp-form-label">Free</label>
                            <select id="line_free" class="form-control"><option value="No" selected>No</option><option value="Yes">Yes</option></select>
                        </div>
                    @endif
                    @if ($freeBottlesToggle)
                        <div class="form-group">
                            <label class="erp-form-label">Free Bottle</label>
                            <select id="line_free_bottles" class="form-control"><option value="No" selected>No</option><option value="Yes">Yes</option></select>
                        </div>
                    @endif
                </div>

                <div class="erp-table-wrap">
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th class="invoice-index">#</th>
                                <th class="invoice-qty">Quantity</th>
                                <th class="invoice-product">Product</th>
                                <th class="invoice-unitprice">Unit Price</th>
                                <th class="invoice-tax">Tax</th>
                                <th class="invoice-priceinctax">Price Inc. Tax</th>
                                <th class="invoice-totalprice">Total Price</th>
                                <th class="invoice-amount">Amount</th>
                                <th class="invoice-disc">Discount</th>
                                <th style="width:80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="invoice_lines_body"></tbody>
                        <tfoot>
                            <tr><td colspan="7"></td><td class="invoice-amount" style="text-align:right;">TOTAL <span id="total_before">0.00</span></td><td colspan="2"></td></tr>
                            <tr><td colspan="7"></td><td class="invoice-amount" style="text-align:right;">DISCOUNT <span id="total_discount">0.00</span></td><td colspan="2"></td></tr>
                            <tr><td colspan="7"></td><td class="invoice-amount" style="text-align:right;">GRAND TOTAL <span id="total_final">0.00</span></td><td colspan="2"></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="erp-card">
                    <div class="erp-card-header"><h3 class="erp-card-title"><i class="fa fa-credit-card"></i> Payment Details</h3></div>
                    <div class="erp-card-body">
                        <div class="payment-grid">
                            <div class="form-group"><label class="erp-form-label">Cash</label><input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" name="payment_cash" id="payment_cash" class="form-control" placeholder="Please Enter" min="0"></div>
                            <div class="form-group"><label class="erp-form-label">Card</label><input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" name="payment_card" id="payment_card" class="form-control" placeholder="Please Enter" min="0"></div>
                            <div class="form-group"><label class="erp-form-label">Credit</label><input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" name="payment_credit" id="payment_credit" class="form-control" placeholder="Please Enter" min="0"></div>
                            <div class="form-group"><label class="erp-form-label">Cheque</label><input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" name="payment_cheque" id="payment_cheque" class="form-control" placeholder="Please Enter" min="0"></div>
                            <div class="form-group"><label class="erp-form-label">Total Paid</label><input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" id="payment_total" class="form-control erp-readonly" readonly></div>
                        </div>
                        <div id="cheque_section" style="margin-top:15px; display:none;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;"><strong>Cheque Details</strong><button type="button" id="add_cheque_row" class="btn btn-xs btn-success"><i class="fa fa-plus"></i></button></div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-condensed" style="margin-bottom:5px;">
                                    <thead><tr><th>Bank</th><th>Branch</th><th>Cheque No</th><th>Cheque Date</th><th style="width:120px;">Amount</th><th style="width:40px;">&nbsp;</th></tr></thead>
                                    <tbody id="cheque_body"></tbody>
                                </table>
                            </div>
                            <div id="cheque_error" style="color:red; font-weight:600; display:none;"></div>
                        </div>
                        <div id="payment_error" style="color:red; font-weight:600; margin-top:8px; display:none;"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="erp-card">
                    <div class="erp-card-header"><h3 class="erp-card-title"><i class="fa fa-calculator"></i> Invoice Summary</h3></div>
                    <div class="erp-card-body">
                        <div class="summary-card">
                            <div class="summary-row"><span>Total</span><span id="summary_total_before">0.00</span></div>
                            <div class="summary-row"><span>Discount</span><span id="summary_total_discount">0.00</span></div>
                            <div class="summary-row"><span>Grand Total</span><span id="summary_total_final">0.00</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="erp-card">
            <div class="erp-card-header"><h3 class="erp-card-title"><i class="fa fa-sticky-note"></i> Notes & Shipping Details</h3></div>
            <div class="erp-card-body">
                <div class="row">
                    <div class="col-md-4"><div class="form-group"><label class="erp-form-label">Invoice Note</label><textarea class="form-control" name="invoice_note" rows="3">{{ old('invoice_note', optional($salesOrder)->invoice_note) }}</textarea></div></div>
                    <div class="col-md-4"><div class="form-group"><label class="erp-form-label">Shipping Note</label><textarea class="form-control" name="shipping_note" rows="3">{{ old('shipping_note', optional($salesOrder)->shipping_note) }}</textarea></div></div>
                    <div class="col-md-4"><div class="form-group"><label class="erp-form-label">Sales Order Note</label><textarea class="form-control" name="sales_order_note" rows="3">{{ old('sales_order_note', optional($salesOrder)->invoice_note ?? '') }}</textarea></div></div>
                    <div class="col-md-12"><div class="form-group"><label class="erp-form-label">Shipping Details</label><textarea class="form-control" name="shipping_details" rows="3">{{ old('shipping_details', optional($salesOrder)->shipping_details) }}</textarea></div></div>
                </div>
            </div>
        </div>

        <div class="action-footer">
            <div><a href="{{ route('distribution.invoices.index') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back</a></div>
            <div>
                <button type="reset" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</button>
                <button type="submit" class="btn btn-primary" id="save_invoice"><i class="fa fa-save"></i> Save Invoice</button>
            </div>
        </div>

        <div id="hidden_inputs_area"></div>
    </form>
</section>

<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    @include('distribution::contacts.quick_create', ['quick_add' => true])
</div>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            // Setup
            const productSearch = document.getElementById('product_search');
            const categorySelect = document.getElementById('category_id');
            const addLineBtn = document.getElementById('add_line');
            const addLineTopBtn = document.getElementById('add_line_top');
            const body = document.getElementById('invoice_lines_body');
            const hiddenArea = document.getElementById('hidden_inputs_area');
            const loadingSheetInfo = document.getElementById('loading_sheet_info');
            const invoiceDate = document.getElementById('invoice_date');

            const cashInput = $('#payment_cash');
            const cardInput = $('#payment_card');
            const creditInput = $('#payment_credit');
            const chequeInput = $('#payment_cheque');
            const totalInput = $('#payment_total');
            const chequeSection = $('#cheque_section');
            const chequeBody = $('#cheque_body');
            const chequeError = $('#cheque_error');
            const paymentError = $('#payment_error');
            const addChequeRowBtn = $('#add_cheque_row');

            // Precision settings from PHP
            const currencyPrecision = {{ $currency_precision ?? 2 }};
            const quantityPrecision = {{ $quantity_precision ?? 2 }};
            const decimalSeparator = '{{ session('currency')['decimal_separator'] ?? '.' }}';
            const thousandSeparator = '{{ session('currency')['thousand_separator'] ?? ',' }}';

            let currentMaxDiscount = null; // null means unlimited discount
            let currentDiscountType = 'fixed'; // 'fixed' or 'percentage'
            let linesCount = 0;
            const salesOrderLines = @json(optional($salesOrder)->lines ?? []);

            // Initialize Select2 for searchable dropdowns (except product_search which starts disabled)
            // Preserve customer_id if already selected
            const currentCustomerId = $('#customer_id').val();

            // Store reference to customer select
            const $customerSelect = $('#customer_id');

            // Count total options before Select2 initialization
            const totalOptions = $customerSelect.find('option').length;
            console.log('Total customer options in select element:', totalOptions);

            // Initialize Select2 with configuration to show ALL options
            $customerSelect.select2({
                placeholder: 'Please Select',
                allowClear: true,
                width: 'resolve',
                minimumResultsForSearch: 0, // Always show search box
                dropdownAutoWidth: true,
                // Ensure data attributes are preserved
                templateResult: function(data) {
                    return data.text;
                },
                templateSelection: function(data) {
                    return data.text;
                }
            });

            // Initialize other Select2 dropdowns
            $('.select2-search').not('#product_search').not('#customer_id').select2({
                placeholder: 'Please Select',
                allowClear: true,
                width: 'resolve',
                minimumResultsForSearch: 0
            });

            // Restore customer selection if it was set
            if (currentCustomerId) {
                $('#customer_id').val(currentCustomerId).trigger('change');
            }

            $(productSearch).select2({
                placeholder: 'Please Select',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0
            });
            $(productSearch).prop('disabled', false);

            // In your $(document).ready function, after initializing elements:

            // Check initial category state
            const initialCategoryId = $(categorySelect).val();
            if (!initialCategoryId || initialCategoryId === '') {
                $(productSearch).prop('disabled', true);
                $(productSearch).select2({
                    placeholder: 'Please select a category first',
                    allowClear: false,
                    width: '100%',
                    disabled: true
                });
            } else {
                loadProducts(initialCategoryId);
            }

            // helper: AJAX get JSON
            function getJSON(url, params = {}) {
                const query = new URLSearchParams(params).toString();
                return fetch(url + (query ? '?' + query : ''), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).then(r => r.json());
            }

            // Format number with precision
            function formatNumber(num, precision) {
                if (num === null || num === undefined || num === '') return '0';
                const numValue = parseFloat(num);
                if (isNaN(numValue)) return '0';
                return numValue.toFixed(precision);
            }

            // Format number with separators
            function formatNumberWithSeparators(num, precision) {
                if (num === null || num === undefined || num === '') return '0';
                const numValue = parseFloat(num);
                if (isNaN(numValue)) return '0';
                const parts = numValue.toFixed(precision).split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
                return parts.join(decimalSeparator);
            }

            function parseAmount(v) {
                const n = parseFloat(v);
                return isNaN(n) ? 0 : n;
            }

            function updatePaymentTotal() {
                const cash = parseAmount(cashInput.val());
                const card = parseAmount(cardInput.val());
                const credit = parseAmount(creditInput.val());
                const cheque = parseAmount(chequeInput.val());
                const total = cash + card + credit + cheque;
                totalInput.val(total.toFixed(currencyPrecision));
            }

            function chequeSum() {
                let sum = 0;
                chequeBody.find('.cheque-amount-input').each(function() {
                    sum += parseAmount($(this).val());
                });
                return sum;
            }

            function addChequeRow() {
                const step = '0.' + '0'.repeat(Math.max(0, currencyPrecision - 1)) + '1';
                const row = $(`
                    <tr>
                        <td><input type="text" name="cheque_bank[]" class="form-control" placeholder="Please Enter"></td>
                        <td><input type="text" name="cheque_branch[]" class="form-control" placeholder="Please Enter"></td>
                        <td><input type="text" name="cheque_no[]" class="form-control" placeholder="Please Enter"></td>
                        <td><input type="date" name="cheque_date[]" class="form-control"></td>
                        <td><input type="number" step="${step}" name="cheque_amount[]" class="form-control cheque-amount-input" placeholder="Please Enter" min="0" value="0"></td>
                        <td style="text-align:center;">
                            <button type="button" class="btn btn-xs btn-danger remove-cheque-row">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
                chequeBody.append(row);
            }

            function ensureChequeSectionVisibility() {
                const chequeBox = parseAmount(chequeInput.val());
                if (chequeBox > 0) {
                    chequeSection.show();
                    if (chequeBody.find('tr').length === 0) {
                        addChequeRow();
                    }
                } else {
                    chequeSection.hide();
                    chequeBody.empty();
                    chequeError.hide();
                }
            }

            function loadLoadingSheetInfo() {
                const vehicleId = document.getElementById('vehicle_id')?.value;
                const dateRaw = document.getElementById('invoice_date')?.value;
                const dateValue = dateRaw ? dateRaw.split('T')[0] : '';
                const categoryId = document.getElementById('category_id')?.value;

                // Check if elements exist before using them
                const loadingSheetInfo = document.getElementById('loading_sheet_info');
                if (!loadingSheetInfo) return;

                if (!vehicleId || !dateValue) {
                    loadingSheetInfo.textContent = '--';
                    return;
                }

                const params = new URLSearchParams({
                    vehicle_id: vehicleId,
                    date: dateValue,
                    product_category_id: categoryId || ''
                });

                fetch('/distribution/daily-summary-sheet/loading-info?' + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.json())
                    .then(resp => {
                        if (loadingSheetInfo) {
                            loadingSheetInfo.textContent = resp.loading_sheet_no || '--';
                        }
                        const sheetNoInput = document.getElementById('loading_sheet_no');
                        if (sheetNoInput) sheetNoInput.value = resp.loading_sheet_no || '';
                    })
                    .catch(() => {
                        if (loadingSheetInfo) {
                            loadingSheetInfo.textContent = '--';
                        }
                        const sheetNoInput = document.getElementById('loading_sheet_no');
                        if (sheetNoInput) sheetNoInput.value = '';
                    });
            }

            function isCategorySelected() {
                const categoryId = $('#category_id').val();
                return categoryId && categoryId !== '';
            }


            function loadProducts(catId) {
                // Validate category is provided
                if (!catId || catId === '') {
                    console.log('No category selected, skipping product load');
                    return;
                }

                const url = '{{ url('distribution/invoices/products') }}';
                const params = {
                    category_id: catId
                };
                // Preserve currently selected product value before destroying Select2
                const currentSelectedValue = $(productSearch).val();

                getJSON(url, params)
                    .then(data => {
                        console.log('Products data received:', data);
                        // Destroy Select2 first if initialized
                        if ($(productSearch).hasClass('select2-hidden-accessible')) {
                            $(productSearch).select2('destroy');
                        }

                        productSearch.innerHTML = '<option value="">Please Select</option>';

                        // Always treat as array - products are already ordered by latest first from controller
                        const arr = Array.isArray(data) ? data : Object.values(data);
                        console.log('Products array:', arr);

                        if (arr && arr.length > 0) {
                            arr.forEach(item => {
                                // Handle both object format {id: X, name: Y} and other formats
                                let productId, productName;
                                if (typeof item === 'object' && item !== null) {
                                    // Check if id exists (even if 0)
                                    productId = (item.id !== undefined && item.id !== null) ? item.id :
                                        (item.product_id !== undefined && item.product_id !== null) ?
                                        item.product_id : '';
                                    // Check if name exists
                                    productName = item.name || item.product_name || item.text || String(
                                        item.id || '');
                                } else {
                                    productId = item;
                                    productName = String(item);
                                }

                                // Only add if we have valid id and name
                                if (productId !== '' && productId !== null && productId !== undefined &&
                                    productName) {
                                    const opt = document.createElement('option');
                                    opt.value = String(productId);
                                    opt.textContent = String(productName);
                                    productSearch.appendChild(opt);
                                }
                            });
                        }

                        // Enable and reinitialize Select2
                        $(productSearch).prop('disabled', false);
                        // Small delay to ensure DOM is ready
                        setTimeout(function() {
                            $(productSearch).select2({
                                placeholder: 'Please Select',
                                allowClear: true,
                                width: '100%',
                                minimumResultsForSearch: 0
                            });

                            // Restore the previously selected value if it still exists in the options
                            if (currentSelectedValue && $(productSearch).find('option[value="' +
                                    currentSelectedValue + '"]').length > 0) {
                                $(productSearch).val(currentSelectedValue).trigger('change');
                            }
                        }, 50);
                    })
                    .catch(err => {
                        console.error('Error loading products:', err);
                        // Reinitialize Select2 even on error
                        $(productSearch).select2({
                            placeholder: 'Please Select',
                            allowClear: true,
                            width: '100%',
                            minimumResultsForSearch: 0
                        });

                        // Restore the previously selected value if it still exists in the options
                        if (currentSelectedValue && $(productSearch).find('option[value="' +
                                currentSelectedValue + '"]').length > 0) {
                            $(productSearch).val(currentSelectedValue).trigger('change');
                        }
                    });
            }


            updatePaymentTotal();
            ensureChequeSectionVisibility();

            if (Array.isArray(salesOrderLines) && salesOrderLines.length > 0) {
                salesOrderLines.forEach(function(line) {
                    const qty = parseFloat(line.qty || 0);
                    const unitPrice = parseFloat(line.unit_price || 0);
                    const amount = parseFloat(line.amount || (qty * unitPrice));
                    const discount = parseFloat(line.discount || 0);
                    const finalAmt = parseFloat(line.final_amount || (amount - discount));
                    const name = line.product && line.product.name ? line.product.name : ('Product #' + line.product_id);
                    const indexNumber = body.querySelectorAll('tr').length + 1;
                    const tr = document.createElement('tr');
                    tr.dataset.amount = amount;
                    tr.dataset.discount = discount;
                    tr.dataset.discountType = line.discount_type || 'fixed';
                    tr.dataset.final = finalAmt;
                    tr.innerHTML = `
                        <td style="text-align:center;">${indexNumber}</td>
                        <td style="text-align:center;">${formatNumberWithSeparators(qty, quantityPrecision)}</td>
                        <td>${name}<input type="hidden" name="product_id[]" value="${line.product_id}"></td>
                        <td style="text-align:right;">${formatNumberWithSeparators(unitPrice, currencyPrecision)}<input type="hidden" name="unit_price[]" value="${unitPrice}"></td>
                        <td style="text-align:center;">-</td>
                        <td style="text-align:right;">${formatNumberWithSeparators(unitPrice, currencyPrecision)}</td>
                        <td style="text-align:right;">${formatNumberWithSeparators(amount, currencyPrecision)}</td>
                        <td style="text-align:right;">${formatNumberWithSeparators(amount, currencyPrecision)}<input type="hidden" name="amount[]" value="${amount}"></td>
                        <td style="text-align:right;">${formatNumberWithSeparators(discount, currencyPrecision)}<input type="hidden" name="discount[]" value="${discount}"><input type="hidden" name="discount_type[]" value="${line.discount_type || 'fixed'}"><input type="hidden" name="final_amount[]" value="${finalAmt}"></td>
                        <td style="text-align:center;">
                            <button type="button" class="btn-remove-line" title="Remove"><i class="fa fa-trash"></i></button>
                            <input type="hidden" name="qty[]" value="${qty}">
                            <input type="hidden" name="unit_id[]" value="${line.unit_id || ''}">
                            <input type="hidden" name="is_free[]" value="${line.is_free || 0}">
                            <input type="hidden" name="is_free_bottles[]" value="${line.is_free_bottles || 0}">
                            <input type="hidden" name="is_free_auto[]" value="${line.is_free_auto || 0}">
                        </td>`;
                    body.appendChild(tr);
                });
                recalcTotals();
            }

            // Modify the category change event to enable/disable product search
            if (addLineTopBtn && addLineBtn) { addLineTopBtn.addEventListener('click', function(){ addLineBtn.click(); }); }

            $(categorySelect).on('change', function() {
                const catId = $(this).val();
                const hasCategory = catId && catId !== '';

                if (hasCategory) {
                    loadProducts(catId || '');
                    // Enable product search
                    $(productSearch).prop('disabled', false);
                    $(productSearch).select2({
                        placeholder: 'Please Select',
                        allowClear: true,
                        width: '100%',
                        minimumResultsForSearch: 0,
                        disabled: false
                    });
                } else {
                    // Disable product search if no category selected
                    $(productSearch).prop('disabled', true);
                    // Clear product search value
                    $(productSearch).val('').trigger('change');
                    // Destroy and recreate with disabled state
                    if ($(productSearch).hasClass('select2-hidden-accessible')) {
                        $(productSearch).select2('destroy');
                    }
                    $(productSearch).select2({
                        placeholder: 'Please select a category first',
                        allowClear: false,
                        width: '100%',
                        disabled: true
                    });
                    // Clear products dropdown
                    productSearch.innerHTML = '<option value="">Please Select</option>';
                }
            });
            cashInput.on('input', updatePaymentTotal);
            cardInput.on('input', updatePaymentTotal);
            creditInput.on('input', updatePaymentTotal);
            chequeInput.on('input', function() {
                ensureChequeSectionVisibility();
                updatePaymentTotal();
            });

            addChequeRowBtn.on('click', function() {
                addChequeRow();
            });

            chequeBody.on('input', '.cheque-amount-input', function() {
                chequeError.hide();
                updatePaymentTotal();
            });

            chequeBody.on('click', '.remove-cheque-row', function() {
                $(this).closest('tr').remove();
                chequeError.hide();
                updatePaymentTotal();
            });

            $('#vehicle_id').on('change', loadLoadingSheetInfo);
            $(invoiceDate).on('change', loadLoadingSheetInfo);
            $(categorySelect).on('change', loadLoadingSheetInfo);
            $('#sales_rep_id').on('change', function() {
                const salesRepId = $(this).val();
                if (!salesRepId) {
                    return;
                }
                getJSON('{{ route('distribution.route_user_maps.routes_by_sales_rep') }}', {
                    sales_rep_id: salesRepId
                }).then(function(routes) {
                    if (!Array.isArray(routes)) {
                        return;
                    }
                    const selected = $('#route_id').val();
                    $('#route_id').empty().append('<option value="">-- Select --</option>');
                    routes.forEach(function(route) {
                        $('#route_id').append('<option value="' + route.id + '">' + route.name + '</option>');
                    });
                    if (routes.length > 1) {
                        $('#route_id').val('');
                    } else if (selected && $('#route_id option[value="' + selected + '"]').length > 0) {
                        $('#route_id').val(selected);
                    } else if (routes.length === 1) {
                        $('#route_id').val(routes[0].id);
                    }
                    $('#route_id').trigger('change');
                });
            });

            const initialSalesRepId = $('#sales_rep_id').val();
            if (initialSalesRepId) {
                $('#sales_rep_id').trigger('change');
            }

            if ($('#vehicle_id').val() && invoiceDate.value) {
                loadLoadingSheetInfo();
            }

            // Function to calculate price inc tax
            function calculatePriceIncTax() {
                const unitPrice = parseFloat($('#line_unit_price').val()) || 0;
                const taxSelect = $('#line_tax_id');
                const selectedTax = taxSelect.find('option:selected');
                const taxRate = parseFloat(selectedTax.data('rate')) || 0;

                let priceIncTax = unitPrice;
                if (taxRate > 0 && unitPrice > 0) {
                    // Calculate: unitPrice * (1 + taxRate/100)
                    priceIncTax = unitPrice * (1 + taxRate / 100);
                }

                $('#line_price_inc_tax').val(formatNumberWithSeparators(parseFloat(priceIncTax),
                    {{ $currency_precision }}));
                // Recalculate total price with discount
                calculateTotalPrice();
            }

            // Function to calculate total price including discount
            function calculateTotalPrice() {
                const priceIncTax = parseFloat($('#line_price_inc_tax').val()) || 0;
                const qty = parseFloat($('#line_qty').val()) || 0;
                const discountType = $('#line_discount_type').val() || 'fixed';
                const discountValue = parseFloat($('#line_discount').val()) || 0;

                // Calculate base total (Price Inc. Tax × Quantity)
                const baseTotal = priceIncTax * qty;

                // Calculate discount amount
                let discountAmount = 0;
                if (discountType === 'percentage') {
                    // Percentage discount: discountValue is a percentage
                    discountAmount = (baseTotal * discountValue) / 100;
                } else {
                    // Fixed discount: discountValue is the fixed amount
                    discountAmount = discountValue;
                }

                // Total Price = Base Total - Discount
                const totalPrice = Math.max(0, baseTotal - discountAmount);

                $('#line_total_price').val(totalPrice.toFixed({{ $currency_precision }}));
                $('#line_total_price_display').val(totalPrice.toFixed({{ $currency_precision }}));
            }

            // Event listeners for tax and unit price changes
            $('#line_tax_id').on('change', function() {
                calculatePriceIncTax();
            });

            $('#line_unit_price').on('input', function() {
                calculatePriceIncTax();
            });

            // Event listener for quantity changes
            $('#line_qty').on('input', function() {
                calculateTotalPrice();
            });

            // Event listeners for discount changes
            $('#line_discount_type').on('change', function() {
                calculateTotalPrice();
            });

            $('#line_discount').on('input', function() {
                calculateTotalPrice();
            });

            // when product selected, fetch product info (unit price, max discount, optionally units)
            $(productSearch).on('change', function() {
                const pid = $(this).val();
                if (!pid) {
                    $('#line_unit_price').val(0);
                    $('#line_price_inc_tax').val(0);
                    $('#line_total_price').val(0);
                    $('#line_tax_id').val('');
                    $('#line_discount').val(0);
                    $('#line_discount').removeAttr('max');
                    currentMaxDiscount = null;
                    currentDiscountType = 'fixed';
                    $('#line_discount_type').val('fixed');
                    return;
                }
                const url = '{{ url('distribution/invoices/product-info') }}';
                getJSON(url, {
                        product_id: pid
                    })
                    .then(resp => {
                        console.log('Product info response:', resp);
                        // resp should contain unit_price and max_discount
                        let unitPrice = 0;
                        if (resp.unit_price !== undefined && resp.unit_price !== null) {
                            unitPrice = parseFloat(resp.unit_price);
                        }
                        console.log('Parsed unit price:', unitPrice, 'from response:', resp.unit_price);
                        // Set the raw numeric value for number input (not formatted string)

                        // Set the raw numeric value for number input (not formatted string)
if (resp.unit_price > 0) {
    $('#line_unit_price').val(formatNumber(resp.unit_price, currencyPrecision));
    console.log('Unit price set to:', resp.unit_price);
}

// Set Price Inc. Tax from the new field
if (resp.price_inc_tax > 0) {
    $('#line_price_inc_tax').val(formatNumber(resp.price_inc_tax, currencyPrecision));
    console.log('Price Inc. Tax set to:', resp.price_inc_tax);
} else if (resp.unit_price > 0) {
    // Fallback to unit_price if price_inc_tax not provided
    $('#line_price_inc_tax').val(formatNumber(resp.unit_price, currencyPrecision));
}

calculateTotalPrice(); // Recalculate total price after setting both

                        // Set default discount type from product settings, but allow user to change it
                        const defaultDiscountType = resp.discount_type || 'fixed';
                        $('#line_discount_type').val(defaultDiscountType);
                        currentDiscountType = defaultDiscountType;
                        // Recalculate total price after setting discount type
                        calculateTotalPrice();

                        // Handle max_discount: null = unlimited, 0 = no discount allowed, >0 = max discount
                        if (resp.max_discount === null || resp.max_discount === undefined) {
                            // No discount rule exists - allow unlimited discount
                            currentMaxDiscount = null;
                            $('#line_discount').removeAttr('max');
                        } else {
                            const maxDisc = parseFloat(resp.max_discount);
                            if (maxDisc === 0) {
                                // Discount explicitly set to 0 - no discount allowed
                                currentMaxDiscount = 0;
                                $('#line_discount').attr('max', 0);
                            } else if (maxDisc > 0) {
                                // Max discount is set
                                currentMaxDiscount = maxDisc;
                                $('#line_discount').attr('max', maxDisc);
                            } else {
                                // Invalid value, allow unlimited
                                currentMaxDiscount = null;
                                $('#line_discount').removeAttr('max');
                            }
                        }

                        // Populate units dropdown with all available units
                        const unitSel = $('#line_unit_id');
                        unitSel.empty();
                        console.log('Units received:', resp.units);

                        if (resp.units && Array.isArray(resp.units) && resp.units.length > 0) {
                            // Add all units
                            resp.units.forEach(u => {
                                if (u && u.id && u.name) {
                                    unitSel.append($('<option>', {
                                        value: u.id,
                                        text: u.name
                                    }));
                                }
                            });

                            console.log('Total units added:', unitSel.find('option').length);

                            // Auto-select if only one unit available
                            if (resp.units.length === 1) {
                                unitSel.val(resp.units[0].id);
                            } else {
                                // Leave empty so user can choose if multiple units
                                unitSel.val('');
                            }
                        } else {
                            // fallback default
                            unitSel.html('<option value="">No Units Available</option>');
                        }
                    })
                    .catch(err => {
                        console.error('Error loading product info:', err);
                        alert('Error loading product information. Please try again.');
                    });
            });

            // recalc totals - FIXED VERSION
            function recalcTotals() {
                let totalBefore = 0;
                let totalDiscount = 0;
                let totalFinal = 0;

                const rows = document.querySelectorAll('#invoice_lines_body tr');
                console.log('Recalculating totals for', rows.length, 'rows');

                rows.forEach((row, index) => {
                    // Get values from dataset (set when row was created)
                    const amount = parseFloat(row.dataset.amount || 0);
                    const discount = parseFloat(row.dataset.discount || 0);
                    const final = parseFloat(row.dataset.final || 0);

                    // Check if this is a free item by looking for "Free Issue" text in the amount cell
                    const amountCell = row.querySelector('td:nth-child(8)');
                    const amountText = amountCell ? amountCell.textContent.trim() : '';
                    const isFreeItem = amountText.includes('Free Issue') || amountText.includes(
                        'Free Bottles');

                    console.log(`Row ${index + 1}:`, {
                        amount,
                        discount,
                        final,
                        isFreeItem,
                        amountText
                    });

                    // Only add to totals if NOT a free item
                    if (!isFreeItem) {
                        totalBefore += amount;
                        totalDiscount += discount;
                        totalFinal += final;
                    } else {
                        console.log('Row', index + 1, 'is free - excluding from totals');
                    }
                });

                // Update the display with formatted numbers
                const totalBeforeEl = document.getElementById('total_before');
                const totalDiscountEl = document.getElementById('total_discount');
                const totalFinalEl = document.getElementById('total_final');

                if (totalBeforeEl) {
                    totalBeforeEl.textContent = formatNumberWithSeparators(totalBefore, 2);
                }

                if (totalDiscountEl) {
                    totalDiscountEl.textContent = formatNumberWithSeparators(totalDiscount, 2);
                }

                if (totalFinalEl) {
                    totalFinalEl.textContent = formatNumberWithSeparators(totalFinal, 2);
                }

                const summaryTotalBeforeEl = document.getElementById('summary_total_before');
                const summaryTotalDiscountEl = document.getElementById('summary_total_discount');
                const summaryTotalFinalEl = document.getElementById('summary_total_final');
                if (summaryTotalBeforeEl) summaryTotalBeforeEl.textContent = formatNumberWithSeparators(totalBefore, 2);
                if (summaryTotalDiscountEl) summaryTotalDiscountEl.textContent = formatNumberWithSeparators(totalDiscount, 2);
                if (summaryTotalFinalEl) summaryTotalFinalEl.textContent = formatNumberWithSeparators(totalFinal, 2);

                // Update hidden grand total input for form submission
                let grandTotalInput = document.getElementById('grand_total_input');
                if (!grandTotalInput) {
                    grandTotalInput = document.createElement('input');
                    grandTotalInput.type = 'hidden';
                    grandTotalInput.id = 'grand_total_input';
                    grandTotalInput.name = 'grand_total';
                    document.getElementById('invoice_form').appendChild(grandTotalInput);
                }
                grandTotalInput.value = totalFinal.toFixed(2);

                console.log('Final totals:', {
                    totalBefore: totalBefore.toFixed(2),
                    totalDiscount: totalDiscount.toFixed(2),
                    totalFinal: totalFinal.toFixed(2)
                });
            }


            // Add line - COMPLETELY FIXED VERSION
            $(addLineBtn).on('click', function() {
                // Validate prerequisites

                const categoryId = $(categorySelect).val();
                if (!categoryId || categoryId === '') {
                    alert('Please select a product category first');
                    $(categorySelect).focus();
                    return;
                }

                // Rest of your existing validation...
                const customerId = $('#customer_id').val();
                if (!customerId) {
                    alert('Please select a customer first');
                    $('#customer_id').focus();
                    return;
                }

                const pid = $(productSearch).val();
                const pname = $(productSearch).find('option:selected').text() || '';
                if (!pid) {
                    alert('Please select a product');
                    $(productSearch).focus();
                    return;
                }

                const unitSel = $('#line_unit_id');
                const unitId = unitSel.val() || '';
                const unitName = unitSel.find('option:selected').text() || '';

                const qty = parseFloat($('#line_qty').val()) || 0;
                const unitPrice = parseFloat($('#line_unit_price').val()) || 0;
                const taxId = $('#line_tax_id').val() || '';
                const taxName = taxId ? $('#line_tax_id option:selected').text() : '';
                const priceIncTax = parseFloat($('#line_price_inc_tax').val()) || unitPrice;
                let discountValue = parseFloat($('#line_discount').val()) || 0;
                const selectedDiscountType = $('#line_discount_type').val() || 'fixed';

                // FREE FLAGS - EXPLICITLY SET TO FALSE FOR REGULAR ITEMS
                // These are ONLY for manually marking items as free, not for auto-loaded free items
                const isFreeManually = $('#line_free').length > 0 && $('#line_free').val() === 'Yes';
                const isFreeBottlesManually = $('#line_free_bottles').length > 0 && $('#line_free_bottles')
                    .val() === 'Yes';

                // For the main product row, it's NOT a free item (free items are added separately)
                const isFreeItem = false; // ALWAYS FALSE for the main product row

                // Validate qty
                if (qty <= 0) {
                    alert('Quantity must be greater than 0');
                    $('#line_qty').focus();
                    return;
                }

                // Calculate amount
                const amount = qty * priceIncTax;
                console.log('Amount calculated:', amount);

                // Calculate discount
                let discount = 0;
                if (selectedDiscountType === 'percentage') {
                    discount = (amount * discountValue) / 100;
                    if (currentMaxDiscount !== null && currentMaxDiscount !== undefined &&
                        currentMaxDiscount > 0) {
                        const maxDiscountAmount = (amount * currentMaxDiscount) / 100;
                        if (discount > maxDiscountAmount) {
                            alert('Discount cannot exceed maximum allowed: ' + formatNumberWithSeparators(
                                currentMaxDiscount, currencyPrecision) + '%');
                            discount = maxDiscountAmount;
                            discountValue = currentMaxDiscount;
                            $('#line_discount').val(formatNumber(discountValue, currencyPrecision));
                        }
                    }
                } else {
                    discount = discountValue;
                    if (currentMaxDiscount === 0 && discount > 0) {
                        alert('Discount is not allowed for this product.');
                        discount = 0;
                        discountValue = 0;
                        $('#line_discount').val(0);
                    } else if (currentMaxDiscount !== null && currentMaxDiscount !== undefined &&
                        currentMaxDiscount > 0 && discount > currentMaxDiscount) {
                        alert('Discount cannot exceed maximum allowed: ' + formatNumberWithSeparators(
                            currentMaxDiscount, currencyPrecision));
                        discount = currentMaxDiscount;
                        discountValue = currentMaxDiscount;
                        $('#line_discount').val(formatNumber(discount, currencyPrecision));
                    }
                }

                const finalAmt = Math.max(0, amount - discount);

                linesCount++;
                const currentRowCount = body.querySelectorAll('tr').length;
                const indexNumber = currentRowCount + 1;

                // Create table row
                const tr = document.createElement('tr');
                tr.dataset.amount = amount; // NOT 0 for regular items
                tr.dataset.discount = discount;
                tr.dataset.discountType = selectedDiscountType;
                tr.dataset.final = finalAmt;

                const totalPrice = priceIncTax * qty;

                tr.innerHTML = `
   
                <td style="text-align:center;">${indexNumber}</td>
    <td style="text-align:center;">${formatNumberWithSeparators(qty, quantityPrecision)}</td>
    <td>${pname}<input type="hidden" name="product_id[]" value="${pid}"></td>
    <td style="text-align:right;">${formatNumberWithSeparators(unitPrice, currencyPrecision)}<input type="hidden" name="unit_price[]" value="${unitPrice}"></td>
    <td style="text-align:center;">${taxName || '-'}</td>
    <td style="text-align:right;">${formatNumberWithSeparators(priceIncTax, currencyPrecision)}</td>
    <td style="text-align:right;">${formatNumberWithSeparators(totalPrice, currencyPrecision)}</td>
    <td class="row-amount-cell" style="text-align:right;">
        ${formatNumberWithSeparators(amount, currencyPrecision)}
        <input type="hidden" name="amount[]" value="${amount}">
    </td>
    <td style="text-align:right;">
        ${formatNumberWithSeparators(discount, currencyPrecision)}
        <input type="hidden" name="discount[]" value="${discountValue}">
        <input type="hidden" name="discount_type[]" value="${selectedDiscountType}">
        <input type="hidden" name="final_amount[]" value="${finalAmt}">
    </td>
    <td style="text-align:center;">
        <button type="button" class="btn-remove-line" title="Remove">
            <i class="fa fa-trash"></i>
        </button>
        <input type="hidden" name="qty[]" value="${qty}">
        <input type="hidden" name="unit_id[]" value="${unitId}">
        <input type="hidden" name="is_free[]" value="0">
        <input type="hidden" name="is_free_bottles[]" value="0">
        <input type="hidden" name="is_free_auto[]" value="0">
    </td>
`;

                body.appendChild(tr);
                console.log('Row added with amount:', amount);

                // Auto-load Free Issues
                const freeCheckUrl = '{{ url('distribution/invoices/free-issues-check') }}';
                fetch(`${freeCheckUrl}?product_id=${pid}&qty=${qty}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(r => r.json())
                    .then(freeItems => {
                        if (!freeItems || !freeItems.length) return;


                        // When adding free items to the invoice
                        freeItems.forEach(fi => {
                            const fiIdx = body.querySelectorAll('tr').length + 1;
                            const fiTr = document.createElement('tr');
                            fiTr.dataset.amount = 0;
                            fiTr.dataset.discount = 0;
                            fiTr.dataset.final = 0;
                            fiTr.style.backgroundColor = '#fff8f8';

                            // Determine the label based on rule type
                            let freeLabel = 'Free Issue';
                            if (fi.is_free_bottles == 1) {
                                freeLabel = 'Free Bottles';
                            }

                            fiTr.innerHTML = `
        <td style="text-align:center;">${fiIdx}</td>
        <td style="text-align:center;">${formatNumberWithSeparators(fi.free_qty, quantityPrecision)}</td>
        <td>${fi.product_name}<input type="hidden" name="product_id[]" value="${fi.product_id}"></td>
        <td style="text-align:right;">0<input type="hidden" name="unit_price[]" value="0"></td>
        <td style="text-align:center;">-</td>
        <td style="text-align:right;">0</td>
        <td style="text-align:right;">0</td>
        <td style="text-align:center; color:red; font-weight:bold;">
            ${freeLabel}
            <input type="hidden" name="amount[]" value="0">
        </td>
        <td style="text-align:right;">
            0
            <input type="hidden" name="discount[]" value="0">
            <input type="hidden" name="discount_type[]" value="fixed">
            <input type="hidden" name="final_amount[]" value="0">
        </td>
        <td style="text-align:center;">
            <button type="button" class="btn-remove-line" title="Remove">
                <i class="fa fa-trash"></i>
            </button>
            <input type="hidden" name="qty[]" value="${fi.free_qty}">
            <input type="hidden" name="unit_id[]" value="">
            <input type="hidden" name="is_free[]" value="${fi.is_free}">
            <input type="hidden" name="is_free_bottles[]" value="${fi.is_free_bottles}">
            <input type="hidden" name="is_free_auto[]" value="1">
        </td>
    `;
                            body.appendChild(fiTr);
                        });

                        recalcTotals();

                        let rowNumber = 1;
                        $(body).find('tr').each(function() {
                            $(this).find('td:first').text(rowNumber++);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching free issues:', error);
                    });

                recalcTotals();

                let idx = 1;
                $(body).find('tr').each(function() {
                    $(this).find('td:first').text(idx++);
                });

                if (linesCount === 1) {
                    $(categorySelect).prop('disabled', true);
                    $(categorySelect).css('background-color', '#f0f0f0');
                }

                $('#line_qty').val('1');
                $('#line_discount').val('0');
                $(productSearch).val('').trigger('change');
                $('#line_unit_id').empty();
                $('#line_unit_price').val(0);
                if ($('#line_free').length) $('#line_free').val('No');
                if ($('#line_free_bottles').length) $('#line_free_bottles').val('No');
            });

            // remove line (event delegation) - handle clicks on button or icon inside
            $(body).on('click', '.btn-remove-line', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const row = $(this).closest('tr');
                row.remove();
                recalcTotals();
                // re-index
                let idx = 1;
                $(body).find('tr').each(function() {
                    $(this).find('td:first').text(idx++);
                });
                linesCount = idx - 1;

                // Unlock category dropdown if all products removed
                if (linesCount === 0) {
                    $(categorySelect).prop('disabled', false);
                    $(categorySelect).css('background-color', '');
                }
            });

            // customer auto fill
            $('#customer_id').on('change', function() {
                const id = $(this).val();
                if (!id) {
                    $('#customer_address').val('');
                    $('#customer_phone').val('');
                    return;
                }

                // Get data from the option's data attributes
                // Access the original select element (not Select2's clone)
                const originalSelect = $(this);
                const optionElement = originalSelect.find('option[value="' + id + '"]');

                // Get the data attributes from the option
                let address = optionElement.data('address') || optionElement.attr('data-address') || '';
                let phone = optionElement.data('phone') || optionElement.attr('data-phone') || '';

                // Set the values
                $('#customer_address').val(address);
                $('#customer_phone').val(phone);

                console.log('Customer info loaded:', {
                    id,
                    address,
                    phone
                });
            });

            // Trigger change event on page load if customer is already selected
            if ($('#customer_id').val()) {
                $('#customer_id').trigger('change');
            }

            $(document).on('click', '#add_new_customer_btn', function() {
                $('.contact_modal').find('select#contact_type').val('customer').trigger('change');
                $('.contact_modal').modal('show');
            });

            // Combine date and time before form submit
            $('#invoice_form').on('submit', function(e) {
                const rows = body.querySelectorAll('tr');
                if (rows.length === 0) {
                    e.preventDefault();
                    alert('Add at least one product line before saving.');
                    return false;
                }

                // Validate customer is selected
                const customerId = $('#customer_id').val();
                if (!customerId) {
                    e.preventDefault();
                    alert('Please select a customer before saving.');
                    $('#customer_id').focus();
                    return false;
                }

                // Convert datetime-local format to backend format (Y-m-d H:i:s)
                const dateInput = document.getElementById('invoice_date');

                if (dateInput && dateInput.value) {
                    // datetime-local format is YYYY-MM-DDTHH:mm, convert to Y-m-d H:i:s
                    const dateTimeValue = dateInput.value.replace('T', ' ') + ':00';
                    console.log('Date/time value:', dateTimeValue);

                    // Remove any existing hidden date input
                    $('input[name="date"][type="hidden"]').remove();

                    // Temporarily rename the datetime-local input so it doesn't submit
                    dateInput.name = 'date_temp';

                    // Create hidden input with the converted datetime value
                    const hiddenInput = $('<input>', {
                        type: 'hidden',
                        name: 'date',
                        value: dateTimeValue
                    });
                    $('#invoice_form').append(hiddenInput);
                }

                // Ensure customer_id is preserved - log it before submit
                let finalCustomerId = $('#customer_id').val();
                console.log('Form submitting with customer_id (before check):', finalCustomerId);

                // If Select2 is initialized, get the actual selected value
                if ($('#customer_id').data('select2')) {
                    const select2Data = $('#customer_id').select2('data');
                    if (select2Data && select2Data.length > 0) {
                        finalCustomerId = select2Data[0].id;
                        console.log('Form submitting with customer_id (from Select2):', finalCustomerId);
                    }
                }

                // Double-check customer_id is still set
                if (!finalCustomerId) {
                    e.preventDefault();
                    alert('Customer selection was lost. Please select a customer again.');
                    $('#customer_id').focus();
                    return false;
                }

                const chequeBoxAmount = parseAmount(chequeInput.val());
                const totalCheques = chequeSum();
                const tol = Math.pow(10, -currencyPrecision);

                if (chequeBoxAmount > 0 || totalCheques > 0) {
                    if (Math.abs(chequeBoxAmount - totalCheques) > tol) {
                        e.preventDefault();
                        chequeError.text('Cheque amounts does not match. Please check again').show();
                        return false;
                    }
                }

                const paymentTotal = parseAmount(totalInput.val());
                const grandTotalHidden = $('#grand_total_input').val();
                let grandTotal = 0;
                if (grandTotalHidden !== undefined && grandTotalHidden !== null && grandTotalHidden !==
                    '') {
                    grandTotal = parseAmount(grandTotalHidden);
                } else {
                    const dt = $('#total_final').attr('data-total');
                    grandTotal = parseAmount(dt !== undefined && dt !== null ? dt : $('#total_final')
                        .text());
                }

                if (Math.abs(paymentTotal - grandTotal) > tol) {
                    e.preventDefault();
                    paymentError.text(
                            'Grand Total Amount does not match with the Total Payments. Need to check')
                        .show();
                    return false;
                }

                paymentError.hide();
                chequeError.hide();

                // Ensure customer_id is in the form as a hidden input if Select2 might have issues
                let customerInput = $('input[name="customer_id"]');
                if (customerInput.length === 0) {
                    $('<input>', {
                        type: 'hidden',
                        name: 'customer_id',
                        value: finalCustomerId
                    }).appendTo('#invoice_form');
                } else {
                    customerInput.val(finalCustomerId);
                }

                console.log('Form submitting with final customer_id:', finalCustomerId);

                // Log category_id before submit - handle Select2 if used
                let categoryId = $('#category_id').val();
                console.log('Form submitting with category_id (before Select2 check):', categoryId);

                // If Select2 is initialized, get the actual selected value
                if ($('#category_id').data('select2')) {
                    const select2Data = $('#category_id').select2('data');
                    if (select2Data && select2Data.length > 0) {
                        categoryId = select2Data[0].id;
                        console.log('Form submitting with category_id (from Select2):', categoryId);
                    }
                }

                console.log('Form submitting with final category_id:', categoryId);
                if (!categoryId) {
                    console.warn('WARNING: category_id is empty!');
                }

                // Ensure category_id is in the form as a hidden input
                let categoryInput = $('input[name="category_id"]');
                if (categoryInput.length === 0) {
                    $('<input>', {
                        type: 'hidden',
                        name: 'category_id',
                        value: categoryId || ''
                    }).appendTo('#invoice_form');
                } else {
                    categoryInput.val(categoryId || '');
                }

                // allow submit; inputs are already appended inside rows as hidden inputs
            });

            // Update discount calculation when discount type changes
            $('#line_discount_type').on('change', function() {
                // Re-validate discount when type changes
                $('#line_discount').trigger('input');
            });

            // Validate discount on input
            $('#line_discount').on('input', function() {
                const discountValue = parseFloat($(this).val()) || 0;
                const qty = parseFloat($('#line_qty').val()) || 0;
                const unitPrice = parseFloat($('#line_unit_price').val()) || 0;
                const amount = qty * unitPrice;
                const selectedDiscountType = $('#line_discount_type').val() || 'fixed';

                if (selectedDiscountType === 'percentage') {
                    // For percentage, validate the percentage value
                    if (currentMaxDiscount === 0 && discountValue > 0) {
                        $(this).get(0).setCustomValidity('Discount is not allowed for this product.');
                    } else if (currentMaxDiscount !== null && currentMaxDiscount !== undefined &&
                        currentMaxDiscount > 0 && discountValue > currentMaxDiscount) {
                        $(this).get(0).setCustomValidity('Discount cannot exceed maximum allowed: ' +
                            formatNumberWithSeparators(currentMaxDiscount, currencyPrecision) + '%');
                    } else if (discountValue < 0 || discountValue > 100) {
                        $(this).get(0).setCustomValidity('Percentage must be between 0 and 100');
                    } else {
                        $(this).get(0).setCustomValidity('');
                    }
                } else {
                    // For fixed amount
                    if (currentMaxDiscount === 0 && discountValue > 0) {
                        $(this).get(0).setCustomValidity('Discount is not allowed for this product.');
                    } else if (currentMaxDiscount !== null && currentMaxDiscount !== undefined &&
                        currentMaxDiscount > 0 && discountValue > currentMaxDiscount) {
                        $(this).get(0).setCustomValidity('Discount cannot exceed maximum allowed: ' +
                            formatNumberWithSeparators(currentMaxDiscount, currencyPrecision));
                    } else {
                        $(this).get(0).setCustomValidity('');
                    }
                }
            });
        });
    </script>
@endsection
