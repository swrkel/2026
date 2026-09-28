@extends('distribution::layouts.app')

@section('title', 'Dis. Invoice Edit')

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
        /* Basic styling to match client red table look */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .business-header {
            flex: 1;
            text-align: center;
        }

        .business-header h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .invoice-header-right {
            text-align: right;
        }

        .invoice-title {
            color: #0b77d1;
            font-weight: 700;
            font-size: 28px;
            display: block;
            margin-bottom: 8px;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .customer-block {
            flex: 1;
            min-width: 300px;
        }

        .invoice-block {
            flex: 1;
            min-width: 300px;
            text-align: right;
        }

        .invoice-block .form-field-row {
            justify-content: flex-end;
        }

        .form-field-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            gap: 0;
        }

        .form-field-row label {
            min-width: 140px;
            margin: 0;
            margin-right: 4px;
        }

        .form-field-row input,
        .form-field-row select {
            flex: 0 0 250px;
            max-width: 250px;
        }

        /* Select2 dropdowns */
        .form-field-row .select2-container {
            width: 250px !important;
            max-width: 250px !important;
        }

        /* Left-align Select2 placeholder text */
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            text-align: left !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            text-align: left !important;
        }

        .select2-results__option {
            text-align: left !important;
        }

        /* red-bordered invoice grid */
        .invoice-table {
            border-collapse: collapse;
            width: 100%;
            border-top: 2px solid #d22;
        }

        .invoice-table th,
        .invoice-table td {
            border: 2px solid #d22;
            padding: 8px;
            vertical-align: middle;
        }

        .invoice-table thead th {
            background: #fff;
            font-weight: 700;
            color: #900;
            border-top: 2px solid #d22;
            text-align: center;
        }

        .btn-remove-line {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 3px;
            cursor: pointer;
        }

        .btn-remove-line:hover {
            background-color: #c82333;
        }

        .invoice-table tbody td {
            height: 34px;
        }

        .invoice-table tfoot td {
            height: 34px;
            font-weight: 700;
            color: #900;
        }

        .invoice-table tfoot td[colspan] {
            border: none !important;
            padding: 0;
        }

        .invoice-table tfoot td.invoice-amount {
            border: 2px solid #d22 !important;
        }

        .invoice-index {
            width: 70px;
            text-align: center;
        }

        .invoice-qty {
            width: 100px;
            text-align: center;
        }

        .invoice-product {
            width: 35%;
        }

        .invoice-unitprice,
        .invoice-amount,
        .invoice-disc {
            width: 140px;
            text-align: right;
        }


        .payment-details {
            margin-top: 40px;
        }

        /* small helpers */
        .small-input {
            width: 100%;
            box-sizing: border-box;
            padding: 6px;
        }

        .btn-add {
            margin-top: 24px;
        }
    </style>

    <section class="content">
        <form method="POST" action="{{ route('distribution.invoices.update', $invoice->id) }}" id="invoice_form">
            @csrf
            @method('PUT')

            <div class="top-header">
                <div class="business-header">
                    <h2>{{ $business->name ?? 'Business Name' }}</h2>
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
                    <div><strong>Contact Number:</strong> {{ $location->mobile ?? ($location->alternate_number ?? '') }}
                    </div>
                </div>
                <div class="invoice-header-right">
                    <div class="invoice-title">Invoice</div>
                    <div><strong>Invoice No:</strong> <span id="invoice_no">{{ $invoice->invoice_no }}</span></div>
                </div>
            </div>

            <div class="invoice-header">
                <div class="customer-block">
                    <div class="form-field-row">
                        <label><strong>Customer:</strong></label>
                        <select name="customer_id" id="customer_id" class="form-control select2-search">
                            <option value="">-- Select customer --</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer['id'] }}"
                                    data-address="{{ htmlspecialchars($customer['address'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                                    data-phone="{{ htmlspecialchars($customer['phone'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                                    {{ $invoice->customer_id == $customer['id'] ? 'selected' : '' }}>
                                    {{ $customer['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-xs btn-primary" id="add_new_customer_btn" style="margin-left:6px;">
                            <i class="fa fa-plus"></i>
                        </button>
                        {{-- Debug: Total customers loaded --}}
                        <script>
                            console.log('Total customers in PHP: {{ count($customers) }}');
                        </script>
                    </div>
                    <div class="form-field-row">
                        <label><strong>Customer Address:</strong></label>
                        <input type="text" id="customer_address" class="small-input" readonly>
                    </div>
                    <div class="form-field-row">
                        <label><strong>Customer Contact No:</strong></label>
                        <input type="text" id="customer_phone" class="small-input" readonly>
                    </div>
                    <div class="form-field-row">
                        <label><strong>Delivery Date:</strong></label>
                        <input type="date" name="delivery_date" class="small-input" value="{{ $invoice->delivery_date }}">
                    </div>
                </div>

                <div class="invoice-block">
                    <div class="form-field-row">
                        <label><strong>Date:</strong></label>
                        <input type="datetime-local" name="date" id="invoice_date" class="small-input"
                            value="{{ date('Y-m-d\TH:i', strtotime($invoice->date)) }}">
                    </div>
                    <div class="form-field-row">
                        <label><strong>Sales Rep:</strong></label>
                        <select name="sales_rep_id" id="sales_rep_id" class="small-input select2-search">
                            <option value="">-- Select --</option>
                            @foreach ($salesReps as $id => $name)
                                <option value="{{ $id }}" {{ $invoice->sales_rep_id == $id ? 'selected' : '' }}>
                                    {{ $name }}</option>
                            @endforeach
                        </select>
                        {{-- Debug: Sales Reps Count = {{ count($salesReps ?? []) }} --}}
                    </div>
                    <div class="form-field-row">
                        <label><strong>Route:</strong></label>
                        <select name="route_id" id="route_id" class="small-input select2-search">
                            <option value="">-- Select --</option>
                            @foreach ($routes as $id => $name)
                                <option value="{{ $id }}" {{ $invoice->route_id == $id ? 'selected' : '' }}>
                                    {{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field-row">
                        <label><strong>Vehicle No:</strong></label>
                        <select name="vehicle_id" id="vehicle_id" class="small-input select2-search">
                            <option value="">-- Select --</option>
                            @foreach ($vehicles as $id => $name)
                                <option value="{{ $id }}" {{ $invoice->vehicle_id == $id ? 'selected' : '' }}>
                                    {{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field-row">
                        <label><strong>Loading Sheet:</strong></label>
                        <div id="loading_sheet_info" style="font-weight:700; padding: 6px;">
                            {{ $invoice->loading_sheet_no ?: '--' }}</div>
                        <input type="hidden" name="loading_sheet_no" id="loading_sheet_no"
                            value="{{ $invoice->loading_sheet_no }}">
                    </div>
                    <div class="form-field-row">
                        <label><strong>Product Category:</strong></label>
                        <select id="category_id" class="small-input select2-search" name="category_id">
                            <option value="">All</option>
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}" {{ $invoice->category_id == $id ? 'selected' : '' }}>
                                    {{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field-row">
                        <label><strong>Note:</strong></label>
                        <textarea class="small-input" name="invoice_note" rows="2">{{ $invoice->invoice_note }}</textarea>
                    </div>
                    <div class="form-field-row">
                        <label><strong>Shipping Status:</strong></label>
                        <select class="small-input" name="shipping_status">
                            @foreach (['ordered' => 'Ordered', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $shipping_key => $shipping_label)
                                <option value="{{ $shipping_key }}" {{ $invoice->shipping_status === $shipping_key ? 'selected' : '' }}>{{ $shipping_label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-bottom: 12px;">
                <div class="col-md-6">
                    <label><strong>Shipping Note</strong></label>
                    <textarea class="form-control" name="shipping_note" rows="2">{{ $invoice->shipping_note }}</textarea>
                </div>
                <div class="col-md-6">
                    <label><strong>Sales Order Note</strong></label>
                    <textarea class="form-control" name="sales_order_note" rows="2">{{ old('sales_order_note', $invoice->sales_order_note ?? (optional($invoice->salesOrder)->invoice_note ?? '')) }}</textarea>
                </div>
                <div class="col-md-12" style="margin-top: 8px;">
                    <label><strong>Shipping Details</strong></label>
                    <textarea class="form-control" name="shipping_details" rows="3">{{ $invoice->shipping_details }}</textarea>
                </div>
            </div>

            {{-- Product search row --}}
            <div style="margin-bottom:12px; display: flex; align-items: flex-end; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 0 0 auto;">
                    <label><strong>Search Products</strong></label>
                    <select id="product_search" class="form-control select2-search" style="width:300px; display:block;">
                        <option value="">Please Select</option>
                    </select>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Unit</label>
                    <select id="line_unit_id" class="form-control"
                        style="width:150px; display:block; min-height: 34px;"></select>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Quantity</label>
                    <input id="line_qty" type="number" step="{{ '0.' . str_repeat('0', $quantity_precision - 1) . '1' }}"
                        value="1" class="form-control" style="width:80px; display:block;" min="0">
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Unit Price</label>
                    <input id="line_unit_price" type="number"
                        step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" value="0"
                        class="form-control" style="width:120px; display:block;" min="0" readonly>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Tax Type</label>
                    <select id="line_tax_id" class="form-control" style="width:120px; display:block;">
                        @if (!empty($taxes))
                            @foreach ($taxes as $tax_id => $tax_name)
                                <option value="{{ $tax_id }}"
                                    @if (!empty($tax_attributes[$tax_id]['data-rate'])) data-rate="{{ $tax_attributes[$tax_id]['data-rate'] }}" @endif>
                                    {{ $tax_name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Price Inc. Tax</label>
                    <input id="line_price_inc_tax" type="number"
                        step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" value="0"
                        class="form-control" style="width:120px; display:block;" min="0" readonly>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Discount Type</label>
                    <select id="line_discount_type" class="form-control" style="width:100px; display:block;">
                        <option value="fixed">Fixed</option>
                        <option value="percentage">Percentage</option>
                    </select>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Discount</label>
                    <input id="line_discount" type="number"
                        step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" value="0"
                        class="form-control" style="width:100px; display:block;" min="0">
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Total Price</label>
                    <input id="line_total_price" type="number"
                        step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" value="0"
                        class="form-control" style="width:120px; display:block;" min="0" readonly>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Free</label>
                    <select id="line_free" class="form-control" style="width:100px; display:block;">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Free Bottles</label>
                    <select id="line_free_bottles" class="form-control" style="width:100px; display:block;">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>&nbsp;</label>
                    <button type="button" id="add_line" class="btn btn-success" style="display:block;">Add</button>
                </div>
            </div>

            {{-- Invoice table (red) --}}
            <div style="overflow:auto;">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th class="invoice-index">Index No</th>
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
                    <tbody id="invoice_lines_body">
                        @foreach ($invoice->lines as $index => $line)
                            @php
                                $priceIncTax = $line->unit_price;
                                $totalPrice = $priceIncTax * $line->qty;
                                // Recalculate amount if it's 0 but qty and price exist (handles newly-stored rows with missing amount)
                                $lineAmount =
                                    $line->is_free || $line->is_free_bottles
                                        ? 0
                                        : ($line->amount > 0
                                            ? $line->amount
                                            : $totalPrice);
                                $lineFinalAmount =
                                    $line->is_free || $line->is_free_bottles
                                        ? 0
                                        : ($line->final_amount > 0
                                            ? $line->final_amount
                                            : $lineAmount - ($line->discount ?? 0));
                            @endphp

                            <tr data-amount="{{ $lineAmount }}" data-discount="{{ $line->discount }}"
                                data-discount-type="{{ $line->discount_type ?? 'fixed' }}"
                                data-final="{{ $lineFinalAmount }}">


                                <td style="text-align:center;">{{ $index + 1 }}</td>
                                <td style="text-align:center;">{{ number_format($line->qty, $quantity_precision) }}</td>
                                <td>{{ $line->product->name ?? '' }}<input type="hidden" name="product_id[]"
                                        value="{{ $line->product_id }}"></td>
                                <td style="text-align:right;">
                                    {{ number_format($line->unit_price, $currency_precision) }}<input type="hidden"
                                        name="unit_price[]" value="{{ $line->unit_price }}"></td>
                                <td style="text-align:center;">-</td>
                                <td style="text-align:right;">{{ number_format($priceIncTax, $currency_precision) }}</td>
                                <td style="text-align:right;">{{ number_format($totalPrice, $currency_precision) }}</td>

                                <td style="text-align:right;">
                                    {{ $line->is_free || $line->is_free_bottles ? '' : number_format($lineAmount, $currency_precision) }}<input
                                        type="hidden" name="amount[]"
                                        value="{{ $line->is_free || $line->is_free_bottles ? 0 : $lineAmount }}"></td>


                                <td style="text-align:right;">
                                    {{ $line->is_free || $line->is_free_bottles ? '0.00' : number_format($line->discount, $currency_precision) }}
                                    <input type="hidden" name="discount[]"
                                        value="{{ $line->is_free || $line->is_free_bottles ? 0 : $line->discount }}">
                                    <input type="hidden" name="discount_type[]"
                                        value="{{ $line->discount_type ?? 'fixed' }}">
                                    <input type="hidden" name="final_amount[]"
                                        value="{{ $line->is_free || $line->is_free_bottles ? 0 : $lineFinalAmount }}">
                                </td>
                                <td style="text-align:center;">
                                    <button type="button" class="btn-remove-line" title="Remove">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                    <input type="hidden" name="qty[]" value="{{ $line->qty }}">
                                    <input type="hidden" name="unit_id[]" value="{{ $line->unit_id ?? '' }}">
                                    <input type="hidden" name="is_free[]" value="{{ $line->is_free }}">
                                    <input type="hidden" name="is_free_bottles[]" value="{{ $line->is_free_bottles }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7"></td>
                            <td class="invoice-amount" style="text-align:right; font-weight:700;">TOTAL <span
                                    id="total_before">0.00</span></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="7"></td>
                            <td class="invoice-amount" style="text-align:right; font-weight:700;">DISCOUNT <span
                                    id="total_discount">0.00</span></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="7"></td>
                            <td class="invoice-amount" style="text-align:right; font-weight:700;">GRAND TOTAL <span
                                    id="total_final">0.00</span></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="payment-details" style="margin-top:20px;">
                <h4>Payment Details</h4>

                <div style="display:flex; gap:20px; flex-wrap:wrap; align-items:flex-end;">
                    <div>
                        <label>Cash</label>
                        <input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}"
                            name="payment_cash" id="payment_cash" class="form-control" placeholder="Please Enter"
                            min="0"
                            value="{{ (float) $invoice->payment_cash > 0 ? (float) $invoice->payment_cash : '' }}">
                    </div>

                    <div>
                        <label>Card</label>
                        <input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}"
                            name="payment_card" id="payment_card" class="form-control" placeholder="Please Enter"
                            min="0"
                            value="{{ (float) $invoice->payment_card > 0 ? (float) $invoice->payment_card : '' }}">
                    </div>

                    <div>
                        <label>Credit</label>
                        <input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}"
                            name="payment_credit" id="payment_credit" class="form-control" placeholder="Please Enter"
                            min="0"
                            value="{{ (float) $invoice->payment_credit > 0 ? (float) $invoice->payment_credit : '' }}">
                    </div>

                    <div>
                        <label>Cheque</label>
                        <input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}"
                            name="payment_cheque" id="payment_cheque" class="form-control" placeholder="Please Enter"
                            min="0"
                            value="{{ (float) $invoice->payment_cheque > 0 ? (float) $invoice->payment_cheque : '' }}">
                    </div>

                    <div>
                        <label style="color:#d22; font-weight:700;">Total</label>
                        <input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}"
                            id="payment_total" class="form-control"
                            style="color:#d22; font-weight:700; border:2px solid #d22;" readonly
                            value="{{ (float) $invoice->payment_total > 0 ? (float) $invoice->payment_total : '' }}">
                    </div>
                </div>

                <div id="cheque_section" style="margin-top:15px; display:none;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                        <strong>Cheque Details</strong>
                        <button type="button" id="add_cheque_row" class="btn btn-xs btn-success">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>

                    <table class="table table-bordered table-condensed" style="margin-bottom:5px;">
                        <thead>
                            <tr>
                                <th>Bank</th>
                                <th>Branch</th>
                                <th>Cheque No</th>
                                <th>Cheque Date</th>
                                <th style="width:120px;">Amount</th>
                                <th style="width:40px;">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody id="cheque_body">
                            @foreach ($invoice->cheques as $cheque)
                                <tr>
                                    <td><input type="text" name="cheque_bank[]" class="form-control"
                                            placeholder="Please Enter" value="{{ $cheque->bank }}"></td>
                                    <td><input type="text" name="cheque_branch[]" class="form-control"
                                            placeholder="Please Enter" value="{{ $cheque->branch }}"></td>
                                    <td><input type="text" name="cheque_no[]" class="form-control"
                                            placeholder="Please Enter" value="{{ $cheque->cheque_no }}"></td>
                                    <td><input type="date" name="cheque_date[]" class="form-control"
                                            value="{{ $cheque->cheque_date }}"></td>
                                    <td><input type="number"
                                            step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}"
                                            name="cheque_amount[]" class="form-control cheque-amount-input"
                                            placeholder="Please Enter" min="0"
                                            value="{{ (float) $cheque->amount }}"></td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn btn-xs btn-danger remove-cheque-row">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div id="cheque_error" style="color:red; font-weight:600; display:none;"></div>
                </div>

                <div id="payment_error" style="color:red; font-weight:600; margin-top:8px; display:none;"></div>
            </div>

            <br>
            <div class="text-right">
                <button type="submit" class="btn btn-primary" id="save_invoice">Update Invoice</button>
            </div>

            {{-- Hidden arrays to be submitted; JS will append inputs here for each line --}}
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

            let currentMaxDiscount = null;
            let currentDiscountType = 'fixed';
            let linesCount = {{ count($invoice->lines) }};

            // ========== CORE FUNCTION: Recalculate totals from actual inputs ==========
            function recalcTotals() {
                let totalBefore = 0;
                let totalDiscount = 0;
                let totalFinal = 0;

                const rows = body.querySelectorAll('tr');

                rows.forEach((row, index) => {
                    // Get the hidden input values directly
                    const amountInput = row.querySelector('input[name="amount[]"]');
                    const discountInput = row.querySelector('input[name="discount[]"]');
                    const finalInput = row.querySelector('input[name="final_amount[]"]');

                    // Check if this is a free item row
                    const isFreeInput = row.querySelector('input[name="is_free[]"]');
                    const isFreeBottlesInput = row.querySelector('input[name="is_free_bottles[]"]');
                    const isFreeRow = (isFreeInput && isFreeInput.value == '1') || (isFreeBottlesInput &&
                        isFreeBottlesInput.value == '1');

                    let amount = amountInput ? parseFloat(amountInput.value) || 0 : 0;
                    let discount = discountInput ? parseFloat(discountInput.value) || 0 : 0;
                    let final = finalInput ? parseFloat(finalInput.value) || 0 : 0;

                    // If amount is 0 but row is not free, recover from Total Price cell and update display
                    if (amount === 0 && !isFreeRow) {
                        const cells = row.querySelectorAll('td');
                        // Total Price is column index 6 (7th column: Index, Qty, Product, UnitPrice, Tax, PriceIncTax, TotalPrice)
                        if (cells[6]) {
                            // Strip ALL non-numeric except decimal point (handles any thousand separator)
                            const rawText = cells[6].textContent.trim();
                            const totalPriceCellVal = parseFloat(rawText.replace(/[^\d.]/g, '')) || 0;
                            if (totalPriceCellVal > 0) {
                                amount = totalPriceCellVal;
                                final = Math.max(0, amount - discount);
                                // Update hidden inputs so form submit is correct
                                if (amountInput) amountInput.value = amount.toFixed(currencyPrecision);
                                if (finalInput) finalInput.value = final.toFixed(currencyPrecision);
                                // ALSO update the visible Amount cell text (cells[7])
                                if (cells[7]) {
                                    // Find the text node (not the hidden input) and update it
                                    const amountCell = cells[7];
                                    const hiddenInput = amountCell.querySelector('input[type="hidden"]');
                                    // Set the text content before the hidden input
                                    if (hiddenInput) {
                                        // Replace text node content
                                        const textNode = Array.from(amountCell.childNodes)
                                            .find(n => n.nodeType === Node.TEXT_NODE);
                                        if (textNode) {
                                            textNode.textContent = formatNumberWithSeparators(amount,
                                                currencyPrecision);
                                        } else {
                                            amountCell.insertBefore(
                                                document.createTextNode(formatNumberWithSeparators(
                                                    amount, currencyPrecision)),
                                                hiddenInput
                                            );
                                        }
                                    } else {
                                        amountCell.textContent = formatNumberWithSeparators(amount,
                                            currencyPrecision);
                                    }
                                }
                            }
                        }
                    }
                    // For free rows, ensure values are 0
                    if (isFreeRow) {
                        amount = 0;
                        discount = 0;
                        final = 0;
                        if (amountInput) amountInput.value = 0;
                        if (discountInput) discountInput.value = 0;
                        if (finalInput) finalInput.value = 0;
                    }

                    totalBefore += amount;
                    totalDiscount += discount;
                    totalFinal += final;

                    // Update dataset for any other functions that might use it
                    row.dataset.amount = amount;
                    row.dataset.discount = discount;
                    row.dataset.final = final;
                });

                // Update the display
                const totalBeforeEl = document.getElementById('total_before');
                const totalDiscountEl = document.getElementById('total_discount');
                const totalFinalEl = document.getElementById('total_final');

                if (totalBeforeEl) totalBeforeEl.textContent = formatNumberWithSeparators(totalBefore,
                    currencyPrecision);
                if (totalDiscountEl) totalDiscountEl.textContent = formatNumberWithSeparators(totalDiscount,
                    currencyPrecision);
                if (totalFinalEl) {
                    totalFinalEl.textContent = formatNumberWithSeparators(totalFinal, currencyPrecision);
                    totalFinalEl.setAttribute('data-total', totalFinal);
                }

                // Update grand total hidden input
                let grandTotalInput = document.getElementById('grand_total_input');
                if (!grandTotalInput) {
                    grandTotalInput = document.createElement('input');
                    grandTotalInput.type = 'hidden';
                    grandTotalInput.id = 'grand_total_input';
                    grandTotalInput.name = 'grand_total';
                    document.getElementById('invoice_form').appendChild(grandTotalInput);
                }
                grandTotalInput.value = totalFinal.toFixed(currencyPrecision);

                // Also update payment total display
                updatePaymentTotal();
            }

            // ========== Formatting functions ==========
            function formatNumber(num, precision) {
                if (num === null || num === undefined || num === '') return '0';
                const numValue = parseFloat(num);
                if (isNaN(numValue)) return '0';
                return numValue.toFixed(precision);
            }

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

            // ========== Payment functions ==========
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

            // ========== Loading functions ==========
            function loadLoadingSheetInfo() {
                const vehicleId = document.getElementById('vehicle_id').value;
                const dateRaw = invoiceDate.value;
                const dateValue = dateRaw ? dateRaw.split('T')[0] : '';
                const categoryId = categorySelect.value;

                if (!vehicleId || !dateValue) {
                    loadingSheetInfo.textContent = '--';
                    return;
                }

                const params = new URLSearchParams({
                    vehicle_id: vehicleId,
                    date: dateValue,
                    product_category_id: categoryId
                });

                fetch('{{ route('distribution.daily_summary.loading_info') }}?' + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.json())
                    .then(resp => {
                        loadingSheetInfo.textContent = resp.loading_sheet_no || '--';
                        const sheetNoInput = document.getElementById('loading_sheet_no');
                        if (sheetNoInput) sheetNoInput.value = resp.loading_sheet_no || '';
                    })
                    .catch(() => {
                        loadingSheetInfo.textContent = '--';
                        const sheetNoInput = document.getElementById('loading_sheet_no');
                        if (sheetNoInput) sheetNoInput.value = '';
                    });
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

            function loadProducts(catId) {
                const url = '{{ url('distribution/invoices/products') }}';
                const params = {
                    category_id: catId || ''
                };
                const currentSelectedValue = $(productSearch).val();

                getJSON(url, params)
                    .then(data => {
                        if ($(productSearch).hasClass('select2-hidden-accessible')) {
                            $(productSearch).select2('destroy');
                        }
                        productSearch.innerHTML = '<option value="">Please Select</option>';
                        const arr = Array.isArray(data) ? data : Object.values(data);
                        if (arr && arr.length > 0) {
                            arr.forEach(item => {
                                let productId, productName;
                                if (typeof item === 'object' && item !== null) {
                                    productId = (item.id !== undefined && item.id !== null) ? item.id :
                                        (item.product_id !== undefined && item.product_id !== null) ?
                                        item.product_id : '';
                                    productName = item.name || item.product_name || item.text || String(
                                        item.id || '');
                                } else {
                                    productId = item;
                                    productName = String(item);
                                }
                                if (productId !== '' && productId !== null && productId !== undefined &&
                                    productName) {
                                    const opt = document.createElement('option');
                                    opt.value = String(productId);
                                    opt.textContent = String(productName);
                                    productSearch.appendChild(opt);
                                }
                            });
                        }
                        $(productSearch).prop('disabled', false);
                        setTimeout(function() {
                            $(productSearch).select2({
                                placeholder: 'Please Select',
                                allowClear: true,
                                width: '100%',
                                minimumResultsForSearch: 0
                            });
                            if (currentSelectedValue && $(productSearch).find('option[value="' +
                                    currentSelectedValue + '"]').length > 0) {
                                $(productSearch).val(currentSelectedValue).trigger('change');
                            }
                        }, 50);
                    })
                    .catch(err => {
                        console.error('Error loading products:', err);
                        $(productSearch).select2({
                            placeholder: 'Please Select',
                            allowClear: true,
                            width: '100%',
                            minimumResultsForSearch: 0
                        });
                        if (currentSelectedValue && $(productSearch).find('option[value="' +
                                currentSelectedValue + '"]').length > 0) {
                            $(productSearch).val(currentSelectedValue).trigger('change');
                        }
                    });
            }

            // ========== Price calculation functions for new row ==========
            function calculatePriceIncTax() {
                const unitPrice = parseFloat($('#line_unit_price').val()) || 0;
                const taxSelect = $('#line_tax_id');
                const selectedTax = taxSelect.find('option:selected');
                const taxRate = parseFloat(selectedTax.data('rate')) || 0;

                let priceIncTax = unitPrice;
                if (taxRate > 0 && unitPrice > 0) {
                    priceIncTax = unitPrice * (1 + taxRate / 100);
                }
                $('#line_price_inc_tax').val(priceIncTax.toFixed(currencyPrecision));
                calculateTotalPrice();
            }

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
                    discountAmount = (baseTotal * discountValue) / 100;
                } else {
                    discountAmount = discountValue;
                }

                // Total Price = Base Total - Discount
                const totalPrice = Math.max(0, baseTotal - discountAmount);

                $('#line_total_price').val(totalPrice.toFixed(currencyPrecision));
            }

            // ========== Initialize Select2 ==========
            const currentCustomerId = $('#customer_id').val();
            const $customerSelect = $('#customer_id');

            $customerSelect.select2({
                placeholder: 'Please Select',
                allowClear: true,
                width: 'resolve',
                minimumResultsForSearch: 0,
                dropdownAutoWidth: true,
                templateResult: function(data) {
                    return data.text;
                },
                templateSelection: function(data) {
                    return data.text;
                }
            });

            $('.select2-search').not('#product_search').not('#customer_id').select2({
                placeholder: 'Please Select',
                allowClear: true,
                width: 'resolve',
                minimumResultsForSearch: 0
            });

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

            // ========== Event Listeners ==========
            $('#line_tax_id').on('change', calculatePriceIncTax);
            $('#line_unit_price').on('input', calculatePriceIncTax);
            $('#line_qty').on('input', calculateTotalPrice);
            $('#line_discount_type').on('change', calculateTotalPrice);
            $('#line_discount').on('input', calculateTotalPrice);

            $(categorySelect).on('change', function() {
                const catId = $(this).val();
                loadProducts(catId || '');
            });

            cashInput.on('input', updatePaymentTotal);
            cardInput.on('input', updatePaymentTotal);
            creditInput.on('input', updatePaymentTotal);
            chequeInput.on('input', function() {
                ensureChequeSectionVisibility();
                updatePaymentTotal();
            });

            addChequeRowBtn.on('click', addChequeRow);
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


            // Product selection - FIXED VERSION for EDIT VIEW
            $(productSearch).on('change', function() {
                const pid = $(this).val();
                if (!pid) {
                    $('#line_unit_price').val(0);
                    $('#line_price_inc_tax').val(0);
                    $('#line_total_price').val(0);
                    $('#line_discount').val(0);
                    $('#line_discount').removeAttr('max');
                    currentMaxDiscount = null;
                    $('#line_discount_type').val('fixed');
                    return;
                }
                const url = '{{ url('distribution/invoices/product-info') }}';
                getJSON(url, {
                        product_id: pid
                    })
                    .then(resp => {
                        console.log('Product info response:', resp);

                        // Get unit price from response
                        let unitPrice = 0;
                        if (resp.unit_price !== undefined && resp.unit_price !== null) {
                            unitPrice = parseFloat(resp.unit_price);
                        }

                        // Get price_inc_tax from response (THIS IS THE KEY FIX!)
                        let priceIncTax = 0;
                        if (resp.price_inc_tax !== undefined && resp.price_inc_tax !== null) {
                            priceIncTax = parseFloat(resp.price_inc_tax);
                        }

                        console.log('Unit price loaded:', unitPrice);
                        console.log('Price Inc. Tax loaded:', priceIncTax);

                        // Set Unit Price
                        if (unitPrice > 0) {
                            $('#line_unit_price').val(unitPrice);
                            $('#line_unit_price').prop('readonly', false);
                        } else {
                            $('#line_unit_price').val(0);
                            console.warn('Product has no unit price set. Response:', resp);
                            alert(
                                'Warning: This product has no unit price set. Please enter price manually.'
                                );
                            $('#line_unit_price').prop('readonly', false);
                        }

                        // Set Price Inc. Tax directly from response (DON'T recalculate!)
                        if (priceIncTax > 0) {
                            $('#line_price_inc_tax').val(priceIncTax);
                            console.log('Price Inc. Tax set to:', priceIncTax);
                        } else if (unitPrice > 0) {
                            // Fallback to unit_price if price_inc_tax not provided
                            $('#line_price_inc_tax').val(unitPrice);
                        } else {
                            $('#line_price_inc_tax').val(0);
                        }

                        // Set default discount type
                        const defaultDiscountType = resp.discount_type || 'fixed';
                        $('#line_discount_type').val(defaultDiscountType);
                        currentDiscountType = defaultDiscountType;

                        // Calculate total price after setting both values
                        calculateTotalPrice();

                        // Handle max discount
                        if (resp.max_discount === null || resp.max_discount === undefined) {
                            currentMaxDiscount = null;
                            $('#line_discount').removeAttr('max');
                        } else {
                            const maxDisc = parseFloat(resp.max_discount);
                            if (maxDisc === 0) {
                                currentMaxDiscount = 0;
                                $('#line_discount').attr('max', 0);
                            } else if (maxDisc > 0) {
                                currentMaxDiscount = maxDisc;
                                $('#line_discount').attr('max', maxDisc);
                            } else {
                                currentMaxDiscount = null;
                                $('#line_discount').removeAttr('max');
                            }
                        }

                        // Populate units dropdown
                        const unitSel = $('#line_unit_id');
                        unitSel.empty();
                        if (resp.units && Array.isArray(resp.units) && resp.units.length > 0) {
                            resp.units.forEach(u => {
                                if (u && u.id && u.name) {
                                    unitSel.append($('<option>', {
                                        value: u.id,
                                        text: u.name
                                    }));
                                }
                            });
                            if (resp.units.length === 1) {
                                unitSel.val(resp.units[0].id);
                            }
                        } else {
                            unitSel.html('<option value="">No Units Available</option>');
                        }
                    })
                    .catch(err => {
                        console.error('Error loading product info:', err);
                        alert(
                            'Error loading product information. Please try again or enter price manually.'
                            );
                        $('#line_unit_price').prop('readonly', false);
                    });
            });

            // ========== ADD LINE - FIXED VERSION ==========
            $(addLineBtn).on('click', function() {
                const customerId = $('#customer_id').val();
                if (!customerId) {
                    alert('Please select a customer first');
                    $('#customer_id').focus();
                    return;
                }

                const categoryId = $(categorySelect).val();
                if (!categoryId) {
                    alert('Please select a product category first');
                    $(categorySelect).focus();
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
                const unitName = unitSel.find('option:selected').text() || '-';

                const qty = parseFloat($('#line_qty').val()) || 0;
                const unitPrice = parseFloat($('#line_unit_price').val()) || 0;
                const taxName = $('#line_tax_id option:selected').text() || '-';
                const priceIncTax = parseFloat($('#line_price_inc_tax').val()) || unitPrice;
                let discountValue = parseFloat($('#line_discount').val()) || 0;
                const selectedDiscountType = $('#line_discount_type').val() || 'fixed';

                const isFreeRow = $('#line_free').length > 0 && $('#line_free').val() === 'Yes';
                const isFreeBottlesRow = $('#line_free_bottles').length > 0 && $('#line_free_bottles')
                    .val() === 'Yes';
                const isFreeItem = isFreeRow || isFreeBottlesRow;

                if (qty <= 0) {
                    alert('Quantity must be greater than 0');
                    $('#line_qty').focus();
                    return;
                }


                // Ensure priceIncTax is valid — fall back to unitPrice if 0
                const effectivePriceIncTax = priceIncTax > 0 ? priceIncTax : unitPrice;
                const amount = qty * effectivePriceIncTax;


                let discount = 0;
                if (selectedDiscountType === 'percentage') {
                    discount = (amount * discountValue) / 100;
                    if (currentMaxDiscount !== null && currentMaxDiscount > 0) {
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
                    } else if (currentMaxDiscount !== null && currentMaxDiscount > 0 && discount >
                        currentMaxDiscount) {
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
                const totalPrice = priceIncTax * qty;

                console.log('DEBUG - amount:', amount, 'qty:', qty, 'priceIncTax:', priceIncTax);
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="text-align:center;">${indexNumber}</td>
                    <td style="text-align:center;">${formatNumberWithSeparators(qty, quantityPrecision)}</td>
                    <td>${pname}<input type="hidden" name="product_id[]" value="${pid}"></td>
                    <td style="text-align:right;">${formatNumberWithSeparators(unitPrice, currencyPrecision)}<input type="hidden" name="unit_price[]" value="${unitPrice}"></td>
                    <td style="text-align:center;">${taxName}</td>
                  
                    <td style="text-align:right;">${formatNumberWithSeparators(effectivePriceIncTax, currencyPrecision)}</td>
<td style="text-align:right;">${formatNumberWithSeparators(qty * effectivePriceIncTax, currencyPrecision)}</td>
<td style="text-align:right;">${!isFreeItem ? formatNumberWithSeparators(amount, currencyPrecision) : ''}<input type="hidden" name="amount[]" value="${!isFreeItem ? amount : 0}"></td>                    <td style="text-align:right;">${isFreeItem ? '0.00' : formatNumberWithSeparators(discount, currencyPrecision)}<input type="hidden" name="discount[]" value="${isFreeItem ? 0 : discountValue}"><input type="hidden" name="discount_type[]" value="${selectedDiscountType}"><input type="hidden" name="final_amount[]" value="${isFreeItem ? 0 : finalAmt}"></td>
                    <td style="text-align:center;">
                        <button type="button" class="btn-remove-line" title="Remove"><i class="fa fa-trash"></i></button>
                        <input type="hidden" name="qty[]" value="${qty}">
                        <input type="hidden" name="unit_id[]" value="${unitId}">
                        <input type="hidden" name="is_free[]" value="${isFreeRow ? 1 : 0}">
                        <input type="hidden" name="is_free_bottles[]" value="${isFreeBottlesRow ? 1 : 0}">
                    </td>
                `;

                body.appendChild(tr);

                // Renumber rows
                let idx = 1;
                $(body).find('tr').each(function() {
                    $(this).find('td:first').text(idx++);
                });

                recalcTotals();

                if (linesCount === 1) {
                    $(categorySelect).prop('disabled', true);
                    $(categorySelect).css('background-color', '#f0f0f0');
                }

                // Reset form
                $('#line_qty').val('1');
                $('#line_discount').val('0');
                $(productSearch).val('').trigger('change');
                $('#line_unit_id').empty();
                $('#line_unit_price').val(0);
                if ($('#line_free').length) $('#line_free').val('No');
                if ($('#line_free_bottles').length) $('#line_free_bottles').val('No');

                // Auto-load Free Issues
                const freeCheckUrl = '{{ url('distribution/invoices/free-issues-check') }}';
                fetch(`${freeCheckUrl}?product_id=${pid}&qty=${qty}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).then(r => r.json()).then(freeItems => {
                    if (!freeItems || !freeItems.length) return;
                    freeItems.forEach(fi => {
                        const fiIdx = body.querySelectorAll('tr').length + 1;
                        const fiTr = document.createElement('tr');
                        fiTr.style.backgroundColor = '#fff8f8';
                        fiTr.innerHTML = `
                            <td style="text-align:center;">${fiIdx}</td>
                            <td style="text-align:center;">${formatNumberWithSeparators(fi.free_qty, quantityPrecision)}</td>
                            <td>${fi.product_name}<input type="hidden" name="product_id[]" value="${fi.product_id}"></td>
                            <td style="text-align:right;">0<input type="hidden" name="unit_price[]" value="0"></td>
                            <td style="text-align:center;">-</td>
                            <td style="text-align:right;">0</td>
                            <td style="text-align:right;">0</td>
                            <td style="text-align:center; color:red; font-weight:bold;">${fi.is_free_bottles == 1 ? 'Free Bottles' : 'Free Issue'}<input type="hidden" name="amount[]" value="0"></td>
                            <td style="text-align:right;">0<input type="hidden" name="discount[]" value="0"><input type="hidden" name="discount_type[]" value="fixed"><input type="hidden" name="final_amount[]" value="0"></td>
                            <td style="text-align:center;">
                                <button type="button" class="btn-remove-line" title="Remove"><i class="fa fa-trash"></i></button>
                                <input type="hidden" name="qty[]" value="${fi.free_qty}">
                                <input type="hidden" name="unit_id[]" value="">
                                <input type="hidden" name="is_free[]" value="${fi.is_free}">
                                <input type="hidden" name="is_free_bottles[]" value="${fi.is_free_bottles}">
                                <input type="hidden" name="is_free_auto[]" value="1">
                            </td>
                        `;
                        body.appendChild(fiTr);
                        recalcTotals();
                        let ri = 1;
                        $(body).find('tr').each(function() {
                            $(this).find('td:first').text(ri++);
                        });
                    });
                }).catch(() => {});
            });

            // ========== REMOVE LINE - FIXED ==========
            $(body).on('click', '.btn-remove-line', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).closest('tr').remove();
                recalcTotals();
                let idx = 1;
                $(body).find('tr').each(function() {
                    $(this).find('td:first').text(idx++);
                });
                linesCount = idx - 1;
                if (linesCount === 0) {
                    $(categorySelect).prop('disabled', false);
                    $(categorySelect).css('background-color', '');
                }
            });

            // ========== Customer auto fill ==========
            $('#customer_id').on('change', function() {
                const id = $(this).val();
                if (!id) {
                    $('#customer_address').val('');
                    $('#customer_phone').val('');
                    return;
                }
                const originalSelect = $(this);
                const optionElement = originalSelect.find('option[value="' + id + '"]');
                let address = optionElement.data('address') || optionElement.attr('data-address') || '';
                let phone = optionElement.data('phone') || optionElement.attr('data-phone') || '';
                $('#customer_address').val(address);
                $('#customer_phone').val(phone);
            });

            if ($('#customer_id').val()) {
                $('#customer_id').trigger('change');
            }

            $(document).on('click', '#add_new_customer_btn', function() {
                $('.contact_modal').find('select#contact_type').val('customer').trigger('change');
                $('.contact_modal').modal('show');
            });

            // ========== Form Submit ==========
            $('#invoice_form').on('submit', function(e) {
                const rows = body.querySelectorAll('tr');
                if (rows.length === 0) {
                    e.preventDefault();
                    alert('Add at least one product line before saving.');
                    return false;
                }

                const customerId = $('#customer_id').val();
                if (!customerId) {
                    e.preventDefault();
                    alert('Please select a customer before saving.');
                    $('#customer_id').focus();
                    return false;
                }

                const dateInput = document.getElementById('invoice_date');
                if (dateInput && dateInput.value) {
                    const dateTimeValue = dateInput.value.replace('T', ' ') + ':00';
                    $('input[name="date"][type="hidden"]').remove();
                    dateInput.name = 'date_temp';
                    $('<input>', {
                        type: 'hidden',
                        name: 'date',
                        value: dateTimeValue
                    }).appendTo('#invoice_form');
                }

                let finalCustomerId = $('#customer_id').val();
                if ($('#customer_id').data('select2')) {
                    const select2Data = $('#customer_id').select2('data');
                    if (select2Data && select2Data.length > 0) {
                        finalCustomerId = select2Data[0].id;
                    }
                }
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
                let grandTotal = grandTotalHidden ? parseAmount(grandTotalHidden) : parseAmount($(
                    '#total_final').text());

                if (Math.abs(paymentTotal - grandTotal) > tol) {
                    e.preventDefault();
                    paymentError.text(
                            'Grand Total Amount does not match with the Total Payments. Need to check')
                        .show();
                    return false;
                }

                paymentError.hide();
                chequeError.hide();

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

                let categoryId = $('#category_id').val();
                if ($('#category_id').data('select2')) {
                    const select2Data = $('#category_id').select2('data');
                    if (select2Data && select2Data.length > 0) {
                        categoryId = select2Data[0].id;
                    }
                }
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
            });

            // ========== Initial Load ==========
            loadProducts($(categorySelect).val() || '');
            updatePaymentTotal();
            ensureChequeSectionVisibility();
            const initialSalesRepId = $('#sales_rep_id').val();
            if (initialSalesRepId) {
                $('#sales_rep_id').trigger('change');
            }
            if ($('#vehicle_id').val() && invoiceDate.value) {
                loadLoadingSheetInfo();
            }

            // Run initial recalculation after everything is loaded
            setTimeout(() => {
                recalcTotals();
            }, 200);
        });
    </script>
@endsection
