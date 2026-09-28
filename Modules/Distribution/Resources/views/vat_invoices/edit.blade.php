@extends('distribution::layouts.app')

@section('title', 'VAT – Edit Dis. Invoice')

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
            margin-bottom: 20px;
        }

        .form-field-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            gap: 8px;
        }

        .form-field-row label {
            flex: 0 0 160px;
            margin: 0;
            text-align: left;
            font-weight: bold;
        }

        .form-field-row input,
        .form-field-row select,
        .form-field-row textarea,
        .form-field-row .select2-container {
            flex: 1;
            width: 100% !important;
            max-width: none !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            text-align: left !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            text-align: left !important;
        }

        .select2-results__option {
            text-align: left !important;
        }

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

        .small-input {
            width: 100%;
            box-sizing: border-box;
            padding: 6px;
        }
    </style>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li>
                            <a href="{{ route('distribution.vat-invoices.index') }}">
                                <i class="fa fa-list" aria-hidden="true"></i> List VAT Dis invoice
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('distribution.vat-invoices.create') }}">
                                <i class="fa fa-plus" aria-hidden="true"></i> Add VAT Dis invoice
                            </a>
                        </li>
                        <li class="active">
                            <a href="#edit_vat_dis_invoice" data-toggle="tab" aria-expanded="true">
                                <i class="fa fa-edit" aria-hidden="true"></i> Edit VAT Dis invoice
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content" style="padding: 20px;">
                        <div class="tab-pane active" id="edit_vat_dis_invoice">
                            <form method="POST" action="{{ route('distribution.vat-invoices.update', $invoice->id) }}" id="vat_invoice_form">
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
                    <div><strong>Contact Number:</strong> {{ $location->mobile ?? ($location->alternate_number ?? '') }}</div>
                </div>

                <div class="invoice-header-right">
                    <div class="invoice-title">Tax Invoice</div>
                    <div><strong>Invoice No:</strong> <span id="invoice_no_preview">{{ $invoice->invoice_no }}</span></div>
                </div>
            </div>

            <div class="invoice-header">
                <div class="row">
                    <!-- Column 1 -->
                    <div class="col-md-4">
                        <div class="form-field-row">
                            <label><strong>Date:</strong></label>
                            <input type="datetime-local" name="date" id="invoice_date" class="small-input"
                                value="{{ \Carbon\Carbon::parse($invoice->date)->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="form-field-row">
                            <label><strong>Customer:</strong></label>
                            <select name="customer_id" id="customer_id" class="form-control select2-search" required>
                                <option value="">-- Select customer --</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer['id'] }}"
                                        data-address="{{ htmlspecialchars($customer['address'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                                        data-phone="{{ htmlspecialchars($customer['phone'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                                        data-vat="{{ htmlspecialchars($customer['vat_no'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                                        {{ $invoice->customer_id == $customer['id'] ? 'selected' : '' }}>
                                        {{ $customer['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field-row">
                            <label><strong>Customer Address:</strong></label>
                            <input type="text" id="customer_address" class="small-input" readonly>
                        </div>
                        <div class="form-field-row">
                            <label><strong>Customer Contact No:</strong></label>
                            <input type="text" id="customer_phone" class="small-input" readonly>
                        </div>
                    </div>

                    <!-- Column 2 -->
                    <div class="col-md-4">
                        <div class="form-field-row">
                            <label><strong>Customer VAT No:</strong></label>
                            <input type="text" name="customer_vat_no" id="customer_vat_no" class="small-input" value="{{ $invoice->customer_vat_no }}">
                        </div>
                        <div class="form-field-row">
                            <label><strong>Place of Supply:</strong></label>
                            <input type="text" name="place_of_supply" class="small-input" value="{{ $invoice->place_of_supply }}">
                        </div>
                        <div class="form-field-row">
                            <label><strong>Additional Info:</strong></label>
                            <textarea class="small-input" name="additional_info" rows="2">{{ $invoice->additional_info }}</textarea>
                        </div>
                    </div>

                    <!-- Column 3 -->
                    <div class="col-md-4">
                        <div class="form-field-row">
                            <label><strong>Sales Rep:</strong></label>
                            <select name="sales_rep_id" id="sales_rep_id" class="small-input select2-search">
                                <option value="">-- Select --</option>
                                @foreach ($salesReps as $id => $name)
                                    <option value="{{ $id }}" {{ $invoice->sales_rep_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field-row">
                            <label><strong>Route:</strong></label>
                            <select name="route_id" id="route_id" class="small-input select2-search">
                                <option value="">-- Select --</option>
                                @foreach ($routes as $id => $name)
                                    <option value="{{ $id }}" {{ $invoice->route_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field-row">
                            <label><strong>Vehicle No:</strong></label>
                            <select name="vehicle_id" id="vehicle_id" class="small-input select2-search">
                                <option value="">-- Select --</option>
                                @foreach ($vehicles as $id => $name)
                                    <option value="{{ $id }}" {{ $invoice->vehicle_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field-row">
                            <label><strong>Product Category:</strong></label>
                            <select id="category_id" class="small-input select2-search" name="category_id">
                                <option value="">All</option>
                                @foreach ($categories as $id => $name)
                                    <option value="{{ $id }}" {{ $invoice->category_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field-row">
                            <label><strong>Delivery Date:</strong></label>
                            <input type="date" name="delivery_date" class="small-input" value="{{ $invoice->delivery_date }}">
                        </div>
                        <div class="form-field-row">
                            <label><strong>Shipping Status:</strong></label>
                            <select name="shipping_status" class="small-input">
                                <option value="ordered" {{ $invoice->shipping_status === 'ordered' ? 'selected' : '' }}>Ordered</option>
                                <option value="packed" {{ $invoice->shipping_status === 'packed' ? 'selected' : '' }}>Packed</option>
                                <option value="shipped" {{ $invoice->shipping_status === 'shipped' ? 'selected' : '' }}>Shipped</option>
                                <option value="delivered" {{ $invoice->shipping_status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="cancelled" {{ $invoice->shipping_status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-bottom: 12px;">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><strong>Note</strong></label>
                        <textarea class="form-control" name="invoice_note" rows="3">{{ $invoice->invoice_note }}</textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><strong>Shipping Note</strong></label>
                        <textarea class="form-control" name="shipping_note" rows="3">{{ $invoice->shipping_note }}</textarea>
                    </div>
                </div>
                <div class="col-md-12" style="margin-top: 8px;">
                    <div class="form-group">
                        <label><strong>Shipping Details</strong></label>
                        <textarea class="form-control" name="shipping_details" rows="3">{{ $invoice->shipping_details }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Product Search --}}
            <div style="margin-bottom:12px; display: flex; align-items: flex-end; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 0 0 auto;">
                    <label><strong>Search Products</strong></label>
                    <select id="product_search" class="form-control select2-search" style="width:300px; display:block;" disabled>
                        <option value="">Please Select</option>
                    </select>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Unit</label>
                    <select id="line_unit_id" class="form-control" style="width:150px; display:block; min-height: 34px;"></select>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Quantity</label>
                    <input id="line_qty" type="number" step="{{ '0.' . str_repeat('0', $quantity_precision - 1) . '1' }}" value="1" class="form-control" style="width:80px; display:block;" min="0">
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Unit Price</label>
                    <input id="line_unit_price" type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" value="0" class="form-control" style="width:120px; display:block;" min="0" readonly>
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
                    <input id="line_price_inc_tax" type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" value="0" class="form-control" style="width:120px; display:block;" min="0" readonly>
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
                    <input id="line_discount" type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" value="0" class="form-control" style="width:100px; display:block;" min="0">
                </div>
            </div>

            {{-- Second row: Total Price display + Free + Free Bottles + Add --}}
            <div style="margin-bottom:12px; display: flex; align-items: flex-end; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 0 0 auto;">
                    <label>Total Price</label>
                    <input type="text" value="0.00" id="line_total_price_display" class="form-control" style="width:120px; display:block;" readonly>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Free</label>
                    <select id="line_free" class="form-control" style="width:80px; display:block;">
                        <option value="No" selected>No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>Free Bottle</label>
                    <select id="line_free_bottles" class="form-control" style="width:100px; display:block;">
                        <option value="No" selected>No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>

                <div style="flex: 0 0 auto;">
                    <label>&nbsp;</label>
                    <button type="button" id="add_line" class="btn btn-success" style="display:block;">Add</button>
                </div>
            </div>

            {{-- Table --}}
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
                        @foreach($invoice->lines as $index => $line)
                            @php
                                $isFree = (bool)$line->is_free;
                                $isFreeBottles = (bool)$line->is_free_bottles;
                                $qty = (float)$line->qty;
                                $unitPrice = (float)$line->unit_price;
                                $priceIncTax = $unitPrice;
                                $totalPrice = $priceIncTax * $qty;
                                $baseAmount = $isFree || $isFreeBottles ? 0.0 : ($qty * $unitPrice);
                                $discount = (float)$line->discount;
                                $finalAmount = (float)$line->final_amount;
                                $unitText = optional($line->product->unit)->name ?? '';
                            @endphp
                            <tr data-line-index="{{ $index + 1 }}">
                                <td class="invoice-index">{{ $index + 1 }}</td>
                                <td class="invoice-qty">{{ $qty }} {{ $unitText }}</td>
                                <td class="invoice-product">
                                    {{ optional($line->product)->name }}
                                    @if($isFree) <span class="label label-info">Free</span> @endif
                                    @if($isFreeBottles) <span class="label label-warning">Free Bottle</span> @endif
                                </td>
                                <td class="invoice-unitprice" style="text-align:right;">{{ number_format($unitPrice, $currency_precision) }}</td>
                                <td style="text-align:center;">-</td>
                                <td class="invoice-priceinctax" style="text-align:right;">{{ number_format($priceIncTax, $currency_precision) }}</td>
                                <td class="invoice-totalprice" style="text-align:right;">{{ number_format($totalPrice, $currency_precision) }}</td>
                                <td class="invoice-amount" style="text-align:right;">{{ number_format($baseAmount, $currency_precision) }}</td>
                                <td class="invoice-disc" style="text-align:right;">{{ number_format($discount, $currency_precision) }}</td>
                                <td style="text-align:center;">
                                    <button type="button" class="btn btn-xs btn-danger remove-line-btn"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7"></td>
                            <td class="invoice-amount" style="text-align:right; font-weight:700;">TOTAL <span id="total_before">0.00</span></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="7"></td>
                            <td class="invoice-amount" style="text-align:right; font-weight:700;">DISCOUNT <span id="total_discount">0.00</span></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="7"></td>
                            <td class="invoice-amount" style="text-align:right; font-weight:700;">GRAND TOTAL <span id="total_final">0.00</span></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Payments --}}
            <div class="payment-details">
                <h4>Payment Details</h4>
                <div style="display:flex; gap:20px; flex-wrap:wrap; align-items:flex-end;">
                    <div>
                        <label>Cash</label>
                        <input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" name="payment_cash" id="payment_cash" class="form-control" placeholder="0.00" min="0" value="{{ $invoice->payment_cash }}">
                    </div>
                    <div>
                        <label>Card</label>
                        <input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" name="payment_card" id="payment_card" class="form-control" placeholder="0.00" min="0" value="{{ $invoice->payment_card }}">
                    </div>
                    <div>
                        <label>Credit</label>
                        <input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" name="payment_credit" id="payment_credit" class="form-control" placeholder="0.00" min="0" value="{{ $invoice->payment_credit }}">
                    </div>
                    <div>
                        <label>Cheque</label>
                        <input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" name="payment_cheque" id="payment_cheque" class="form-control" placeholder="0.00" min="0" value="{{ $invoice->payment_cheque }}">
                    </div>
                    <div>
                        <label style="color:#d22; font-weight:700;">Total</label>
                        <input type="number" step="{{ '0.' . str_repeat('0', $currency_precision - 1) . '1' }}" id="payment_total" class="form-control" style="color:#d22; font-weight:700; border:2px solid #d22;" readonly>
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
                            @foreach($invoice->cheques as $cheque)
                                <tr>
                                    <td><input type="text" name="cheque_bank[]" class="form-control" required placeholder="Bank" value="{{ $cheque->bank }}"></td>
                                    <td><input type="text" name="cheque_branch[]" class="form-control" required placeholder="Branch" value="{{ $cheque->branch }}"></td>
                                    <td><input type="text" name="cheque_no[]" class="form-control" required placeholder="Cheque No" value="{{ $cheque->cheque_no }}"></td>
                                    <td><input type="date" name="cheque_date[]" class="form-control" required value="{{ $cheque->cheque_date }}"></td>
                                    <td><input type="number" step="any" name="cheque_amount[]" class="form-control cheque-amount-input" required placeholder="Amount" min="0" value="{{ $cheque->amount }}"></td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn btn-xs btn-danger remove-cheque-row"><i class="fa fa-trash"></i></button>
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
                <button type="submit" class="btn btn-primary" id="save_invoice">Save Invoice</button>
            </div>

            <div id="hidden_inputs_area">
                @foreach($invoice->lines as $index => $line)
                    <div id="hidden_line_{{ $index + 1 }}">
                        <input type="hidden" name="product_id[]" value="{{ $line->product_id }}">
                        <input type="hidden" name="qty[]" value="{{ (float)$line->qty }}">
                        <input type="hidden" name="unit_id[]" value="{{ $line->unit_id }}">
                        <input type="hidden" name="unit_price[]" value="{{ (float)$line->unit_price }}">
                        <input type="hidden" name="discount[]" value="{{ (float)$line->discount }}">
                        <input type="hidden" name="discount_type[]" value="fixed">
                        <input type="hidden" name="final_amount[]" value="{{ (float)$line->final_amount }}">
                        <input type="hidden" name="is_free[]" value="{{ $line->is_free ? 1 : 0 }}">
                        <input type="hidden" name="is_free_bottles[]" value="{{ $line->is_free_bottles ? 1 : 0 }}">
                        <input type="hidden" name="is_free_auto[]" value="0">
                    </div>
                @endforeach
            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            const productSearch = document.getElementById('product_search');
            const categorySelect = document.getElementById('category_id');
            const addLineBtn = document.getElementById('add_line');
            const body = document.getElementById('invoice_lines_body');
            const hiddenArea = document.getElementById('hidden_inputs_area');

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

            const currencyPrecision = {{ $currency_precision ?? 2 }};
            const quantityPrecision = {{ $quantity_precision ?? 2 }};

            let currentMaxDiscount = null;
            let currentDiscountType = 'fixed';
            let linesCount = {{ count($invoice->lines) }};

            // Customer selection handler
            $('#customer_id').select2({
                placeholder: 'Please Select',
                allowClear: true,
                width: '100%'
            }).on('change', function() {
                const selected = $(this).find(':selected');
                $('#customer_address').val(selected.data('address') || '');
                $('#customer_phone').val(selected.data('phone') || '');
                $('#customer_vat_no').val(selected.data('vat') || '');
            });

            // Trigger change on load to fill customer details
            $('#customer_id').trigger('change');

            $('.select2-search').not('#product_search').not('#customer_id').select2({
                placeholder: 'Please Select',
                allowClear: true,
                width: '100%'
            });

            $(productSearch).select2({
                placeholder: 'Please Select',
                allowClear: true,
                width: '100%'
            });

            function loadProducts(catId) {
                if (!catId) {
                    $(productSearch).empty().append('<option value="">Please Select</option>').trigger('change');
                    $(productSearch).prop('disabled', true);
                    if ($(productSearch).hasClass('select2-hidden-accessible')) {
                        $(productSearch).select2('destroy');
                    }
                    $(productSearch).select2({
                        placeholder: 'Please select a category first',
                        allowClear: false,
                        width: '100%'
                    });
                    $('#line_unit_price').prop('readonly', true);
                    return;
                }

                $.ajax({
                    url: "{{ route('distribution.invoices.products') }}",
                    data: { category_id: catId },
                    success: function(products) {
                        $(productSearch).empty().append('<option value="">Please Select</option>');
                        products.forEach(p => {
                            $(productSearch).append('<option value="'+p.id+'">'+p.name+'</option>');
                        });
                        $(productSearch).prop('disabled', false);
                        if ($(productSearch).hasClass('select2-hidden-accessible')) {
                            $(productSearch).select2('destroy');
                        }
                        $(productSearch).select2({
                            placeholder: 'Please Select',
                            allowClear: true,
                            width: '100%'
                        });
                        $(productSearch).trigger('change');
                    }
                });
            }

            // Category change product loading
            $(categorySelect).on('change', function() {
                loadProducts($(this).val());
            });

            // Check initial category state
            const initialCategoryId = $(categorySelect).val();
            if (!initialCategoryId) {
                $(productSearch).prop('disabled', true);
                $(productSearch).select2({
                    placeholder: 'Please select a category first',
                    allowClear: false,
                    width: '100%'
                });
                $('#line_unit_price').prop('readonly', true);
            } else {
                loadProducts(initialCategoryId);
            }

            function formatNumber(num, precision) {
                const n = parseFloat(num);
                return isNaN(n) ? '0' : n.toFixed(precision);
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
                        <td><input type="text" name="cheque_bank[]" class="form-control" required placeholder="Bank"></td>
                        <td><input type="text" name="cheque_branch[]" class="form-control" required placeholder="Branch"></td>
                        <td><input type="text" name="cheque_no[]" class="form-control" required placeholder="Cheque No"></td>
                        <td><input type="date" name="cheque_date[]" class="form-control" required></td>
                        <td><input type="number" step="${step}" name="cheque_amount[]" class="form-control cheque-amount-input" required placeholder="Amount" min="0" value="0"></td>
                        <td style="text-align:center;">
                            <button type="button" class="btn btn-xs btn-danger remove-cheque-row"><i class="fa fa-trash"></i></button>
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

            // Trigger cheque visibility on load
            ensureChequeSectionVisibility();
            updatePaymentTotal();

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

                const baseTotal = priceIncTax * qty;
                let discountAmount = 0;
                if (discountType === 'percentage') {
                    discountAmount = (baseTotal * discountValue) / 100;
                } else {
                    discountAmount = discountValue;
                }

                const totalPrice = Math.max(0, baseTotal - discountAmount);
                $('#line_total_price_display').val(totalPrice.toFixed(currencyPrecision));
            }

            $('#line_unit_price, #line_tax_id').on('input change', calculatePriceIncTax);
            $('#line_qty, #line_discount_type, #line_discount').on('input change', calculateTotalPrice);

            // Fetch product info
            $(productSearch).on('change', function() {
                const pid = $(this).val();
                if (!pid) {
                    $('#line_unit_price').val(0);
                    $('#line_price_inc_tax').val(0);
                    $('#line_total_price_display').val(0);
                    $('#line_discount').val(0);
                    currentMaxDiscount = null;
                    return;
                }

                $.ajax({
                    url: '{{ url("distribution/invoices/product-info") }}',
                    data: { product_id: pid },
                    success: function(resp) {
                        $('#line_unit_price').val(parseFloat(resp.unit_price || 0).toFixed(currencyPrecision));
                        $('#line_discount_type').val(resp.discount_type || 'fixed');
                        currentMaxDiscount = resp.max_discount !== null ? parseFloat(resp.max_discount) : null;
                        
                        // Populate unit dropdown
                        const unitSel = $('#line_unit_id');
                        unitSel.empty();
                        if (resp.units && resp.units.length > 0) {
                            resp.units.forEach(u => {
                                unitSel.append($('<option>', { value: u.id, text: u.name }));
                            });
                        }
                        calculatePriceIncTax();
                    }
                });
            });

            // Add line button click
            $(addLineBtn).on('click', function() {
                const pid = $(productSearch).val();
                if (!pid) return;

                const prodText = $(productSearch).find('option:selected').text();
                const qty = parseFloat($('#line_qty').val()) || 0;
                const unitPrice = parseFloat($('#line_unit_price').val()) || 0;
                const taxSelect = $('#line_tax_id');
                const taxId = taxSelect.val() || '';
                const taxName = taxId ? taxSelect.find('option:selected').text() : '';
                const priceIncTax = parseFloat($('#line_price_inc_tax').val()) || unitPrice;
                const discountVal = parseFloat($('#line_discount').val()) || 0;
                const discountType = $('#line_discount_type').val();
                const unitId = $('#line_unit_id').val() || '';
                const unitText = $('#line_unit_id option:selected').text() || '';

                const isFree = $('#line_free').val() === 'Yes' ? 1 : 0;
                const isFreeBottles = $('#line_free_bottles').val() === 'Yes' ? 1 : 0;

                const baseAmount = isFree || isFreeBottles ? 0 : (qty * priceIncTax);
                let discountAmount = 0;
                if (!isFree && !isFreeBottles) {
                    if (discountType === 'percentage') {
                        discountAmount = (baseAmount * discountVal) / 100;
                    } else {
                        discountAmount = discountVal;
                    }
                }

                const finalAmount = Math.max(0, baseAmount - discountAmount);

                linesCount++;

                const row = $(`
                    <tr data-line-index="${linesCount}">
                        <td class="invoice-index">${linesCount}</td>
                        <td class="invoice-qty">${qty} ${unitText}</td>
                        <td class="invoice-product">${prodText} ${isFree ? '<span class="label label-info">Free</span>' : ''} ${isFreeBottles ? '<span class="label label-warning">Free Bottle</span>' : ''}</td>
                        <td class="invoice-unitprice" style="text-align:right;">${formatNumber(unitPrice, currencyPrecision)}</td>
                        <td style="text-align:center;">${taxName || '-'}</td>
                        <td class="invoice-priceinctax" style="text-align:right;">${formatNumber(priceIncTax, currencyPrecision)}</td>
                        <td class="invoice-totalprice" style="text-align:right;">${formatNumber(priceIncTax * qty, currencyPrecision)}</td>
                        <td class="invoice-amount" style="text-align:right;">${formatNumber(baseAmount, currencyPrecision)}</td>
                        <td class="invoice-disc" style="text-align:right;">${formatNumber(discountAmount, currencyPrecision)}</td>
                        <td style="text-align:center;">
                            <button type="button" class="btn btn-xs btn-danger remove-line-btn"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                `);

                $(body).append(row);

                // Add hidden inputs for the form submit
                $(hiddenArea).append(`
                    <div id="hidden_line_${linesCount}">
                        <input type="hidden" name="product_id[]" value="${pid}">
                        <input type="hidden" name="qty[]" value="${qty}">
                        <input type="hidden" name="unit_id[]" value="${unitId}">
                        <input type="hidden" name="unit_price[]" value="${unitPrice}">
                        <input type="hidden" name="discount[]" value="${discountVal}">
                        <input type="hidden" name="discount_type[]" value="${discountType}">
                        <input type="hidden" name="final_amount[]" value="${finalAmount}">
                        <input type="hidden" name="is_free[]" value="${isFree}">
                        <input type="hidden" name="is_free_bottles[]" value="${isFreeBottles}">
                        <input type="hidden" name="is_free_auto[]" value="0">
                    </div>
                `);

                // Reset search row
                $(productSearch).val('').trigger('change');
                $('#line_qty').val(1);
                $('#line_discount').val(0);
                $('#line_free').val('No');
                $('#line_free_bottles').val('No');

                calculateTotals();
            });

            // Remove line button click
            $(body).on('click', '.remove-line-btn', function() {
                const row = $(this).closest('tr');
                const idx = row.data('line-index');
                row.remove();
                $(`#hidden_line_${idx}`).remove();
                
                // Re-index remaining rows
                let i = 0;
                $(body).find('tr').each(function() {
                    i++;
                    $(this).find('.invoice-index').text(i);
                });
                calculateTotals();
            });

            function calculateTotals() {
                let totalBefore = 0;
                let totalDiscount = 0;
                let totalFinal = 0;

                $(hiddenArea).find('div').each(function() {
                    const finalAmount = parseFloat($(this).find('input[name="final_amount[]"]').val()) || 0;
                    const discount = parseFloat($(this).find('input[name="discount[]"]').val()) || 0;
                    const isFree = parseInt($(this).find('input[name="is_free[]"]').val()) || 0;
                    const isFreeBottles = parseInt($(this).find('input[name="is_free_bottles[]"]').val()) || 0;

                    if (!isFree && !isFreeBottles) {
                        const baseAmt = finalAmount + discount;
                        totalBefore += baseAmt;
                        totalDiscount += discount;
                        totalFinal += finalAmount;
                    }
                });

                $('#total_before').text(formatNumber(totalBefore, currencyPrecision));
                $('#total_discount').text(formatNumber(totalDiscount, currencyPrecision));
                $('#total_final').text(formatNumber(totalFinal, currencyPrecision));
                updatePaymentTotal();
            }

            // Run initial calculate totals
            calculateTotals();

            // Save validations
            $('#vat_invoice_form').on('submit', function(e) {
                paymentError.hide();
                chequeError.hide();

                if ($(body).find('tr').length === 0) {
                    e.preventDefault();
                    paymentError.text('Please add at least one product line.').show();
                    return false;
                }

                const totalFinal = parseFloat($('#total_final').text()) || 0;
                const totalPaid = parseFloat($('#payment_total').val()) || 0;

                if (Math.abs(totalFinal - totalPaid) > 0.01) {
                    e.preventDefault();
                    paymentError.text('Total payment entered (' + totalPaid.toFixed(currencyPrecision) + ') must match Grand Total (' + totalFinal.toFixed(currencyPrecision) + ').').show();
                    return false;
                }

                const chequeVal = parseAmount(chequeInput.val());
                if (chequeVal > 0) {
                    const chkSum = chequeSum();
                    if (Math.abs(chequeVal - chkSum) > 0.01) {
                        e.preventDefault();
                        chequeError.text('Sum of cheque details (' + chkSum.toFixed(currencyPrecision) + ') must match Cheque payment (' + chequeVal.toFixed(currencyPrecision) + ').').show();
                        return false;
                    }
                }
            });
        });
    </script>
@endsection
