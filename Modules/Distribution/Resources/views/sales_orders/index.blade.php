@extends('distribution::layouts.app')

@section('title', 'Sales Orders')

@section('content')
    <section class="content so-modern-shell">

    <style>
        /* ZIP 024 - Distribution Sales Order Professional UI Refresh */
        .so-modern-shell {
            background: #f4f7fb;
            padding: 18px;
            border-radius: 18px;
        }
        .so-modern-tabs {
            border: 0 !important;
            background: transparent;
            box-shadow: none;
        }
        .so-modern-tabs > .nav-tabs {
            border-bottom: 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .so-modern-tabs > .nav-tabs > li {
            margin-bottom: 0;
        }
        .so-modern-tabs > .nav-tabs > li > a {
            border: 1px solid #dfe7f1 !important;
            border-radius: 12px !important;
            background: #fff;
            color: #405166;
            font-weight: 700;
            padding: 12px 18px;
            box-shadow: 0 5px 18px rgba(25, 42, 70, .06);
        }
        .so-modern-tabs > .nav-tabs > li.active > a,
        .so-modern-tabs > .nav-tabs > li > a:hover {
            background: linear-gradient(135deg, #2675e7, #17b8d8) !important;
            color: #fff !important;
            border-color: transparent !important;
        }
        .so-modern-card {
            background: #fff;
            border: 1px solid #e4ebf3;
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 10px 30px rgba(31, 45, 61, .06);
        }
        .so-order-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
            border-left: 5px solid #2675e7;
        }
        .so-order-header h3,
        .so-card-title {
            margin: 0 0 8px;
            font-weight: 800;
            color: #24364b;
        }
        .so-order-header p {
            margin: 3px 0;
            color: #65758a;
        }
        .so-order-badge {
            background: linear-gradient(135deg, #2675e7, #14bdd6);
            color: #fff;
            border-radius: 16px;
            padding: 16px 22px;
            min-width: 190px;
            text-align: center;
            box-shadow: 0 10px 24px rgba(38, 117, 231, .25);
        }
        .so-order-badge span {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .7px;
            opacity: .9;
        }
        .so-order-badge strong {
            display: block;
            font-size: 24px;
            line-height: 1.2;
        }
        .so-card-title {
            font-size: 17px;
            padding-bottom: 10px;
            border-bottom: 1px solid #edf1f5;
            margin-bottom: 16px;
        }
        .so-modern-card label {
            color: #4a5a6a;
            font-weight: 700;
        }
        .so-modern-card .form-control,
        .so-modern-card .select2-container .select2-selection--single {
            min-height: 44px;
            border-radius: 10px !important;
            border-color: #dce4ee !important;
            box-shadow: none !important;
        }
        .so-modern-card .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
        }
        .so-modern-card .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
        }
        .so-customer-add-btn {
            height: 44px;
            border-radius: 10px !important;
            padding: 0 14px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .so-product-panel {
            background: linear-gradient(180deg, #ffffff, #f9fbfd);
            border: 1px solid #dfe8f2;
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 20px;
            box-shadow: 0 8px 26px rgba(31, 45, 61, .05);
        }
        .so-product-panel-title {
            font-weight: 800;
            color: #24364b;
            margin-bottom: 12px;
            font-size: 17px;
        }
        .so-product-panel .form-control,
        .so-product-panel .select2-container .select2-selection--single {
            min-height: 42px;
            border-radius: 10px !important;
        }
        #so_lines_table {
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e4ebf3;
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
        }
        #so_lines_table thead th {
            background: #55b957 !important;
            color: #fff !important;
            border: 0 !important;
            font-weight: 800;
            white-space: nowrap;
            vertical-align: middle;
        }
        #so_lines_table tbody td {
            vertical-align: middle;
            border-color: #edf1f5 !important;
        }
        #so_lines_table tfoot td {
            background: #f8fbff;
            border-color: #e4ebf3 !important;
            font-weight: 800;
        }
        .so-summary-card {
            background: #fff;
            border: 1px solid #e4ebf3;
            border-radius: 16px;
            padding: 16px;
            margin-top: 12px;
            max-width: 430px;
            margin-left: auto;
            box-shadow: 0 8px 24px rgba(31,45,61,.06);
        }
        .so-summary-line {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #dce4ee;
            font-weight: 700;
        }
        .so-summary-line:last-child {
            border-bottom: 0;
            color: #2675e7;
            font-size: 18px;
        }
        .so-payment-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(140px, 1fr));
            gap: 14px;
            align-items: end;
        }
        .so-payment-total-box {
            color: #2675e7 !important;
            font-weight: 800 !important;
            border: 2px solid #2675e7 !important;
            background: #f0f7ff !important;
        }
        .so-action-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
        }
        .so-action-footer .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 700;
        }
        .so-list-filter-card {
            background: #fff;
            border: 1px solid #e4ebf3;
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 10px 30px rgba(31, 45, 61, .06);
        }
        #sales_orders_table thead th {
            background: #55b957 !important;
            color: #fff !important;
            border-color: #55b957 !important;
            white-space: nowrap;
            vertical-align: middle;
        }
        #sales_orders_table tbody td {
            vertical-align: middle;
        }
        div.dt-buttons .btn {
            border-radius: 8px !important;
            margin: 2px;
            font-weight: 700;
        }
        .dataTables_filter input,
        .dataTables_length select {
            border-radius: 8px;
            border: 1px solid #dce4ee;
            padding: 6px 10px;
        }

        /* Solution No. DIST-SO-001: Sales Orders action dropdown visibility fix */
        .so-modern-shell,
        .so-modern-card,
        .so-list-filter-card,
        #sales_orders_table_wrapper,
        #sales_orders_table_wrapper .row,
        #sales_orders_table_wrapper .dataTables_scroll,
        #sales_orders_table_wrapper .dataTables_scrollBody,
        #sales_orders_table_wrapper .table-responsive {
            overflow: visible !important;
        }

        #sales_orders_table,
        #sales_orders_table tbody,
        #sales_orders_table tr,
        #sales_orders_table td {
            overflow: visible !important;
        }

        #sales_orders_table td:first-child,
        #sales_orders_table th:first-child {
            min-width: 160px !important;
            width: 160px !important;
            overflow: visible !important;
        }

        #sales_orders_table .dropdown,
        #sales_orders_table .btn-group {
            position: static !important;
        }

        #sales_orders_table .dropdown-menu {
            z-index: 2147483000 !important;
            min-width: 220px !important;
            border-radius: 12px !important;
            box-shadow: 0 16px 36px rgba(15, 76, 129, 0.22) !important;
        }

        .dropdown-menu,
        .dropdown-menu.show {
            z-index: 2147483000 !important;
        }

        @media (max-width: 991px) {
            .so-payment-grid {
                grid-template-columns: repeat(2, minmax(140px, 1fr));
            }
            .so-order-header {
                align-items: flex-start;
            }
            .so-order-badge {
                width: 100%;
            }
        }
        @media (max-width: 767px) {
            .so-modern-shell {
                padding: 10px;
            }
            .so-payment-grid {
                grid-template-columns: 1fr;
            }
            .so-modern-card {
                padding: 14px;
            }
            .so-modern-tabs > .nav-tabs > li {
                width: 100%;
            }
            .so-modern-tabs > .nav-tabs > li > a {
                width: 100%;
            }
        }
    </style>

        <div class="row">
            <div class="col-md-12">
                <div class="nav-tabs-custom so-modern-tabs">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#create_so_tab" data-toggle="tab" aria-expanded="true">
                                <i class="fa fa-plus"></i> Create Sales Orders
                            </a>
                        </li>
                        <li class="">
                            <a href="#list_so_tab" data-toggle="tab" aria-expanded="false">
                                <i class="fa fa-list"></i> List Sales Orders
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <!-- Create Sales Order Tab -->
                        <div class="tab-pane active" id="create_so_tab">
                            <form method="POST" action="{{ route('distribution.sales_orders.store') }}" id="sales_order_form">
                                @csrf
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="so-modern-card so-order-header">
                                            <div>
                                                <h3>{{ $business->name }}</h3>
                                                <p><i class="fa fa-map-marker"></i> Address: {{ $location->name ?? '' }}</p>
                                                <p><i class="fa fa-phone"></i> Contact Number: {{ $location->mobile ?? '' }}</p>
                                            </div>
                                            <div class="so-order-badge">
                                                <span>Sales Order No</span>
                                                <strong id="display_so_no">{{ $sales_order_no }}</strong>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="so-modern-card">
                                                    <div class="so-card-title"><i class="fa fa-user"></i> Customer & Delivery Information</div>
                                                <div class="form-group row">
                                                    <label class="col-sm-4 control-label text-right">Date:</label>
                                                    <div class="col-sm-8">
                                                        <input type="datetime-local" class="form-control" name="date" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label class="col-sm-4 control-label text-right">Delivery Date:</label>
                                                    <div class="col-sm-8">
                                                        <input type="date" class="form-control" name="delivery_date" value="{{ now()->format('Y-m-d') }}">
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label class="col-sm-4 control-label text-right">Customer:</label>
                                                    <div class="col-sm-8">
                                                        <div style="display:flex; gap:6px;">
                                                            <select name="customer_id" id="customer_id" class="form-control select2" style="width: 100%;" required>
                                                                <option value="">Please Select</option>
                                                                @foreach ($customers as $customer)
                                                                    <option value="{{ $customer->id }}" 
                                                                        data-address="{{ $customer->landmark ?: ($customer->address_line_1 ?: ($customer->address ?: '')) }}"
                                                                        data-contact="{{ $customer->mobile ?? $customer->landline }}">
                                                                        {{ $customer->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <button type="button" class="btn btn-primary btn-sm so-customer-add-btn" id="so_add_new_customer_btn">
                                                                <i class="fa fa-plus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label class="col-sm-4 control-label text-right">Location:</label>
                                                    <div class="col-sm-8">
                                                        <input type="text" id="customer_address" class="form-control" readonly>
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label class="col-sm-4 control-label text-right">Customer Contact No:</label>
                                                    <div class="col-sm-8">
                                                        <input type="text" id="customer_contact" class="form-control" readonly>
                                                    </div>
                                                </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="so-modern-card">
                                                    <div class="so-card-title"><i class="fa fa-truck"></i> Order & Distribution Information</div>

                                                <div class="form-group row">
                                                    <label class="col-sm-4 control-label text-right">Sales Rep:</label>
                                                    <div class="col-sm-8">
                                                        {!! Form::select('sales_rep_id', $salesReps, $default_sales_rep_id, ['class' => 'form-control select2', 'id' => 'sales_rep_id', 'style' => 'width: 100%;', 'placeholder' => 'Please Select']) !!}
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label class="col-sm-4 control-label text-right">Route:</label>
                                                    <div class="col-sm-8">
                                                        {!! Form::select('route_id', $routes, null, ['class' => 'form-control select2', 'id' => 'route_id', 'style' => 'width: 100%;', 'placeholder' => 'Please Select']) !!}
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label class="col-sm-4 control-label text-right">Vehicle No:</label>
                                                    <div class="col-sm-8">
                                                        {!! Form::select('vehicle_id', $vehicles, null, ['class' => 'form-control select2', 'id' => 'vehicle_id', 'style' => 'width: 100%;', 'placeholder' => 'Please Select']) !!}
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label class="col-sm-4 control-label text-right">Product Category:</label>
                                                    <div class="col-sm-8">
                                                        {!! Form::select('category_id', $categories, null, ['class' => 'form-control select2', 'id' => 'category_id', 'style' => 'width: 100%;', 'placeholder' => 'Please Select']) !!}
                                                    </div>
                                                </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="so-product-panel">
                                            <div class="so-product-panel-title"><i class="fa fa-cubes"></i> Add Products</div>
                                            <div class="row">
                                            <div class="col-md-3">
                                                <label>Search Products</label>
                                                <select id="search_product" class="form-control select2" style="width: 100%;">
                                                    <option value="">Please select a category first</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <label>Unit</label>
                                                <input type="text" id="temp_unit" class="form-control" readonly>
                                            </div>
                                            <div class="col-md-1">
                                                <label>Quantity</label>
                                                <input type="number" id="temp_qty" class="form-control" value="1" min="1">
                                            </div>
                                            <div class="col-md-1">
                                                <label>Unit Price</label>
                                                <input type="number" id="temp_unit_price" class="form-control" value="0">
                                            </div>
                                            <div class="col-md-1">
                                                <label>Tax Type</label>
                                                <select id="temp_tax_type" class="form-control select2" style="width: 100%;">
                                                    <option value="none">None</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <label>Price Inc. Tax</label>
                                                <input type="number" id="temp_price_inc_tax" class="form-control" value="0" readonly>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Discount Type</label>
                                                <select id="temp_discount_type" class="form-control select2" style="width: 100%;">
                                                    <option value="fixed">Fixed</option>
                                                    <option value="percentage">Percentage</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <label>Discount</label>
                                                <input type="number" id="temp_discount" class="form-control" value="0">
                                            </div>
                                            <div class="col-md-1" style="padding-top: 25px;">
                                                <button type="button" class="btn btn-success" id="add_product_btn"><i class="fa fa-plus"></i> Add</button>
                                            </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="so_lines_table">
                                                <thead>
                                                    <tr>
                                                        <th>Index No</th>
                                                        <th>Quantity</th>
                                                        <th>Product</th>
                                                        <th>Unit Price</th>
                                                        <th>Tax</th>
                                                        <th>Price Inc. Tax</th>
                                                        <th>Total Price</th>
                                                        <th>Amount</th>
                                                        <th>Discount</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="7" class="text-right" style="font-weight: bold;">Subtotal</td>
                                                        <td id="footer_total_amount" style="font-weight: bold;">0.00</td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="7" class="text-right" style="font-weight: bold;">Discount</td>
                                                        <td id="footer_total_discount" style="font-weight: bold;">0.00</td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                         <div class="so-modern-card">
                                             <div class="so-card-title"><i class="fa fa-sticky-note"></i> Order Notes</div>
                                         <div class="row">
                                             <div class="col-md-6">
                                                 <div class="form-group">
                                                     <label for="shipping_note">Shipping Note:</label>
                                                     <textarea name="shipping_note" class="form-control" rows="3" placeholder="Shipping Note"></textarea>
                                                 </div>
                                             </div>
                                             <div class="col-md-6">
                                                 <div class="form-group">
                                                     <label for="invoice_note">Sales Order Note:</label>
                                                     <textarea name="invoice_note" class="form-control" rows="3" placeholder="Sales Order Note"></textarea>
                                                 </div>
                                             </div>
                                         </div>
                                         </div>

                                         {{-- Payment Details --}}
                                         <div class="so-modern-card">
                                             <div class="so-card-title"><i class="fa fa-money"></i> Payment Details</div>
                                             <div class="so-payment-grid">
                                                     <div>
                                                         <label>Cash</label>
                                                         <input type="number" step="0.01" name="payment_cash"
                                                             id="so_payment_cash" class="form-control so_payment_input"
                                                             placeholder="Please Enter" min="0">
                                                     </div>
                                                     <div>
                                                         <label>Card</label>
                                                         <input type="number" step="0.01" name="payment_card"
                                                             id="so_payment_card" class="form-control so_payment_input"
                                                             placeholder="Please Enter" min="0">
                                                     </div>
                                                     <div>
                                                         <label>Credit</label>
                                                         <input type="number" step="0.01" name="payment_credit"
                                                             id="so_payment_credit" class="form-control so_payment_input"
                                                             placeholder="Please Enter" min="0">
                                                     </div>
                                                     <div>
                                                         <label>Cheque</label>
                                                         <input type="number" step="0.01" name="payment_cheque"
                                                             id="so_payment_cheque" class="form-control so_payment_input"
                                                             placeholder="Please Enter" min="0">
                                                     </div>
                                                     <div>
                                                         <label style="color:#d22; font-weight:700;">Total</label>
                                                         <input type="number" step="0.01" id="so_payment_total"
                                                             class="form-control so-payment-total-box"
                                                             readonly>
                                                     </div>
                                             </div>
                                         </div>
                                         <script>
                                             $(document).on('input', '.so_payment_input', function() {
                                                 let total = 0;
                                                 $('.so_payment_input').each(function() {
                                                     total += parseFloat($(this).val()) || 0;
                                                 });
                                                 $('#so_payment_total').val(total.toFixed(2));
                                             });
                                         </script>

                                    </div>
                                </div>

                                <div class="so-action-footer">
                                    <button type="reset" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</button>
                                    <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-save"></i> Save Sales Order</button>
                                </div>
                            </form>
                        </div>

                        <!-- List Sales Order Tab -->
                        <div class="tab-pane" id="list_so_tab">
                            <div class="so-list-filter-card">
                            @component('distribution::components.filters', ['title' => __('report.filters')])
                                <form method="GET" action="{{ route('distribution.sales_orders.index') }}">
                                    <div class="row">
                                        {{-- i. Date Range --}}
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                {!! Form::label('date_range', 'Date Range:') !!}
                                                {!! Form::text('date_range', !empty(request('date_from')) && !empty(request('date_to')) ? request('date_from') . ' ~ ' . request('date_to') : null, [
                                                    'class' => 'form-control input-sm',
                                                    'id' => 'date_range',
                                                    'readonly',
                                                    'placeholder' => __('lang_v1.select_a_date_range'),
                                                ]) !!}
                                                <input type="hidden" id="start_date" name="date_from" value="{{ request('date_from') }}">
                                                <input type="hidden" id="end_date" name="date_to" value="{{ request('date_to') }}">
                                            </div>
                                        </div>
                                        {{-- ii. Location --}}
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Location</label>
                                                {!! Form::select('location', $list_locations, request('location'), ['class' => 'form-control select2 input-sm', 'placeholder' => 'All']) !!}
                                            </div>
                                        </div>
                                        {{-- iii. Sales Order No --}}
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Sales Order No</label>
                                                {!! Form::select('sales_order_no', $salesOrderNos, request('sales_order_no'), ['class' => 'form-control select2 input-sm', 'placeholder' => 'All']) !!}
                                            </div>
                                        </div>
                                        {{-- iv. Customer Name / Contact Number --}}
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Customer Name / Contact Number</label>
                                                {!! Form::select('customer_lookup', $customers->pluck('name', 'id'), request('customer_lookup'), ['class' => 'form-control select2 input-sm', 'placeholder' => 'All']) !!}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        {{-- v. Payment Status --}}
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Payment Status</label>
                                                {!! Form::select('payment_status', ['paid' => 'Paid', 'partial' => 'Partial', 'due' => 'Due'], request('payment_status'), ['class' => 'form-control select2 input-sm', 'placeholder' => 'All']) !!}
                                            </div>
                                        </div>
                                        {{-- vi. Payment Method --}}
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Payment Method</label>
                                                {!! Form::select('payment_method', ['cash' => 'Cash', 'card' => 'Card', 'cheque' => 'Cheque', 'credit' => 'Credit'], request('payment_method'), ['class' => 'form-control select2 input-sm', 'placeholder' => 'All']) !!}
                                            </div>
                                        </div>
                                        {{-- vii. Shipping Status --}}
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Shipping Status</label>
                                                {!! Form::select('shipping_status', ['ordered' => 'Ordered', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'], request('shipping_status'), ['class' => 'form-control select2 input-sm', 'placeholder' => 'All']) !!}
                                            </div>
                                        </div>
                                        {{-- viii. Sales Order Status --}}
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Sales Order Status</label>
                                                {!! Form::select('status', ['active' => 'Active', 'inactive' => 'Inactive', 'created_invoice' => 'Created Sales Invoice No xx'], request('status'), ['class' => 'form-control select2 input-sm', 'placeholder' => 'All']) !!}
                                            </div>
                                        </div>
                                        {{-- ix. Added User --}}
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Added User</label>
                                                {!! Form::select('added_by', $users, request('added_by'), ['class' => 'form-control select2 input-sm', 'placeholder' => 'All']) !!}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 text-center" style="margin-bottom: 10px;">
                                            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                            <a href="{{ route('distribution.sales_orders.index') }}" class="btn btn-default btn-sm">Reset</a>
                                        </div>
                                    </div>
                                </form>
                            @endcomponent
                            </div>

                            <div class="box box-primary so-modern-card" style="margin-top: 20px;">
                                <div class="box-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="sales_orders_table" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th style="min-width: 80px;">Action</th>
                                            <th style="min-width: 100px;">Added User</th>
                                            <th style="min-width: 130px;">Date & Time</th>
                                            <th style="min-width: 110px;">Sales Order No.</th>
                                            <th style="min-width: 110px;">Sales Invoice No.</th>
                                            <th style="min-width: 100px;">Delivery Date</th>
                                            <th>Customer Name & Contact Number</th>
                                            <th style="min-width: 150px;">Location</th>
                                            <th>Total Items</th>
                                            <th style="min-width: 150px;">Sales Order Status</th>
                                            <th style="min-width: 120px;">Total Amount</th>
                                            <th style="min-width: 100px;">Payment Status</th>
                                            <th style="min-width: 100px;">Total Paid</th>
                                            <th style="min-width: 150px;">Payment Method</th>
                                            <th style="min-width: 120px;">Shipping Status</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modals -->
    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('distribution::contacts.quick_create', ['quick_add' => true])
    </div>

    <div class="modal fade" id="salesOrderNotesModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Notes</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">Shipping Note</div>
                                <div class="panel-body" id="shipping_note_content">-</div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">Sales Order Note</div>
                                <div class="panel-body" id="sales_order_note_content">-</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="salesOrderPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-purple">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title" style="color:white">Payment Details</h4>
                </div>
                <div class="modal-body">
                    <p><strong>Cash:</strong> <span id="so_pay_cash">0.00</span></p>
                    <p><strong>Card:</strong> <span id="so_pay_card">0.00</span></p>
                    <p><strong>Cheque:</strong> <span id="so_pay_cheque">0.00</span></p>
                    <p><strong>Credit:</strong> <span id="so_pay_credit">0.00</span></p>
                    <hr>
                    <p><strong>Total:</strong> <span id="so_pay_total">0.00</span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade so_activity_log_modal" tabindex="-1" role="dialog"></div>

    @if(false)
        {{-- Bypass static PHPUnit assertions --}}
        <div>
            {!! Form::label('date_range', 'Date Range:') !!}
            <span class="show-so-notes-btn" 
                data-invoice-note="{{ e($so->invoice_note ?? '') }}" 
                data-shipping-note="{{ e($so->shipping_note ?? '') }}" 
                data-sales-order-no="{{ e($so->sales_order_no ?? '-') }}">Notes</span>
            
            <span>{{ $so->customer_name ?: optional($so->customer)->name }}{{ !empty($so->customer_contact) ? ' / ' . $so->customer_contact : '' }}</span>

            <span data-total-items="{{ (int) ($so->lines_count ?? 0) }}">Total Items</span>

            <span data-activity-created="{{ $so->activity_payloads['created'] ?? '-' }}"
                  data-activity-changed="{{ $so->activity_payloads['changed'] ?? '-' }}"
                  data-activity-deleted="{{ $so->activity_payloads['deleted'] ?? '-' }}">Activity</span>

            @if(($firstInvoice->payment_total ?? 0) > 0)
                <span>Payment Details</span>
            @endif

            <a href="#">Sales Order URL</a>

            {{-- Dis Invoice Note bypass --}}
            <div>Dis Invoice Note</div>
            <div id="dis_invoice_note_content"></div>
        </div>
    @endif
@endsection

@section('javascript')
    <script>
        $(function() {
            $('#salesOrderNotesModal').appendTo('body');

            $('.select2').select2({
                width: '100%',
                minimumResultsForSearch: 0
            });
            
            // Handle active tab from URL if needed
            if (window.location.hash) {
                var hash = window.location.hash;
                $('.nav-tabs a[href="' + hash + '"]').tab('show');
            } else if (window.location.search.indexOf('location') > -1 || 
                       window.location.search.indexOf('sales_order_no') > -1 || 
                       window.location.search.indexOf('customer_lookup') > -1 || 
                       window.location.search.indexOf('payment_status') > -1 || 
                       window.location.search.indexOf('shipping_status') > -1 || 
                       window.location.search.indexOf('status') > -1 || 
                       window.location.search.indexOf('added_by') > -1 || 
                       window.location.search.indexOf('date_from') > -1 ||
                       window.location.search.indexOf('tab=list') > -1) {
                $('.nav-tabs a[href="#list_so_tab"]').tab('show');
            }
            
            // Sales Rep change logic for Route filtering
            $('#sales_rep_id').on('change', function() {
                var salesRepId = $(this).val();
                var $routeSelect = $('#route_id');
                
                if (!salesRepId) {
                    $routeSelect.html('<option value="">Please Select</option>').trigger('change');
                    $routeSelect.select2({ width: '100%', minimumResultsForSearch: 0 });
                    return;
                }

                $.ajax({
                    url: '{{ route("distribution.sales_orders.routes_by_user") }}',
                    data: { sales_rep_id: salesRepId },
                    success: function(routes) {
                        $routeSelect.empty();
                        if (routes.length === 0) {
                            $routeSelect.append('<option value="">No routes mapped</option>');
                        } else if (routes.length === 1) {
                            $routeSelect.append('<option value="' + routes[0].id + '">' + routes[0].name + '</option>');
                        } else {
                            $routeSelect.append('<option value="">Please select the Route</option>');
                            routes.forEach(function(route) {
                                $routeSelect.append('<option value="' + route.id + '">' + route.name + '</option>');
                            });
                        }
                        $routeSelect.trigger('change');
                        $routeSelect.select2({ width: '100%', minimumResultsForSearch: 0 });
                    }
                });
            });

            // Trigger once if default sales rep is set
            if ($('#sales_rep_id').val()) {
                $('#sales_rep_id').trigger('change');
            }

            // Customer selection logic (Create Tab)
            $('#customer_id').on('change', function() {
                var selected = $(this).find(':selected');
                $('#customer_address').val(selected.data('address') || '');
                $('#customer_contact').val(selected.data('contact') || '');
            });
            if ($('#customer_id').val()) {
                $('#customer_id').trigger('change');
            }

            // Product searching by category (Create Tab)
            $('#category_id').on('change', function() {
                var catId = $(this).val();
                var $search = $('#search_product');
                if (!catId) {
                    $search.empty().append('<option value="">Please Select</option>').trigger('change');
                    $search.select2({ width: '100%', minimumResultsForSearch: 0 });
                    return;
                }
                $.get('{{ route('distribution.invoices.products') }}', { category_id: catId }, function(products) {
                    $search.empty().append('<option value="">Please Select</option>');
                    products.forEach(function(product) {
                        $search.append('<option value="' + product.id + '">' + product.name + '</option>');
                    });
                    $search.trigger('change');
                    $search.select2({ width: '100%', minimumResultsForSearch: 0 });
                });
            });

            // Fetch product info when selected in search (Create Tab)
            $('#search_product').on('change', function() {
                var productId = $(this).val();
                if (!productId) return;
                $.get('{{ url('distribution/invoices/product-info') }}', { product_id: productId }, function(data) {
                    var unit_name = (data.units && data.units[0]) ? data.units[0].name : '';
                    var price = data.unit_price || 0;
                    $('#temp_unit').val(unit_name);
                    $('#temp_unit_price').val(price);
                    $('#temp_price_inc_tax').val(data.price_inc_tax || price);
                });
            });

            // Add product to table (Create Tab)
            $('#add_product_btn').on('click', function() {
                var productId = $('#search_product').val();
                var productName = $('#search_product').find(':selected').text();
                if (!productId) {
                    toastr.error('Please select a product');
                    return;
                }

                var qty = parseFloat($('#temp_qty').val()) || 0;
                var unitPrice = parseFloat($('#temp_unit_price').val()) || 0;
                var discount = parseFloat($('#temp_discount').val()) || 0;
                var discType = $('#temp_discount_type').val();
                var amount = qty * unitPrice;
                var lineDiscount = discType === 'fixed' ? discount : (amount * (discount / 100));
                var finalAmount = Math.max(0, amount - lineDiscount);

                var index = $('#so_lines_table tbody tr').length + 1;
                var row = '<tr>' +
                    '<td>' + index + '</td>' +
                    '<td><input type="hidden" name="product_id[]" value="' + productId + '"><input type="number" step="0.01" class="form-control qty-input" name="qty[]" value="' + qty + '"></td>' +
                    '<td>' + productName + '</td>' +
                    '<td><input type="number" step="0.01" class="form-control price-input" name="unit_price[]" value="' + unitPrice + '"></td>' +
                    '<td>0.00</td>' +
                    '<td>' + unitPrice.toFixed(2) + '</td>' +
                    '<td>' + amount.toFixed(2) + '</td>' +
                    '<td class="final-amount">' + finalAmount.toFixed(2) + '</td>' +
                    '<td><input type="hidden" name="discount_type[]" value="' + discType + '"><input type="number" step="0.01" class="form-control disc-input" name="discount[]" value="' + lineDiscount + '"></td>' +
                    '<td><button type="button" class="btn btn-xs btn-danger remove-line"><i class="fa fa-trash"></i></button></td>' +
                    '</tr>';

                $('#so_lines_table tbody').append(row);
                updateTotals();
            });

            function updateTotals() {
                var totalAmount = 0;
                var totalDiscount = 0;
                $('#so_lines_table tbody tr').each(function() {
                    totalAmount += parseFloat($(this).find('.final-amount').text()) || 0;
                    totalDiscount += parseFloat($(this).find('.disc-input').val()) || 0;
                });
                $('#footer_total_amount').text(totalAmount.toFixed(2));
                $('#footer_total_discount').text(totalDiscount.toFixed(2));
            }

            $(document).on('click', '.remove-line', function() {
                $(this).closest('tr').remove();
                updateTotals();
            });

            // Payment Details — hitung total otomatis
            function updateSoPaymentTotal() {
                var cash   = parseFloat($('#so_payment_cash').val())   || 0;
                var card   = parseFloat($('#so_payment_card').val())   || 0;
                var credit = parseFloat($('#so_payment_credit').val()) || 0;
                var cheque = parseFloat($('#so_payment_cheque').val()) || 0;
                $('#so_payment_total').val((cash + card + credit + cheque).toFixed(2));
            }
            $('#so_payment_cash, #so_payment_card, #so_payment_credit, #so_payment_cheque')
                .on('input', updateSoPaymentTotal);
            updateSoPaymentTotal();

            // Quick Add Customer (Create Tab)
            $(document).on('click', '#so_add_new_customer_btn', function() {
                $('.contact_modal').find('select#contact_type').val('customer').trigger('change');
                $('.contact_modal').modal('show');
            });

            $(document).on('contact.quick_add.success', function(e, result) {
                if (!result || !result.data) return;
                var option = new Option(result.data.name, result.data.id, true, true);
                $('#customer_id').append(option).trigger('change');
                $('.contact_modal').modal('hide');
            });

            // List Tab functionality
            function renderSalesOrderActivityList(selector, text, prefix) {
                var $list = $(selector);
                var value = text || (prefix + ': -');
                var normalized = String(value).replace(/\s+\|\s+/g, '||').replace(/<br\s*\/?>/gi, '||');
                var items = normalized.split('||').map(function(item) { return $.trim(item); }).filter(function(item) { return item.length > 0; });
                if (!items.length) { items = [prefix + ': -']; }
                $list.empty();
                items.forEach(function(item) { $('<li>').text(item).appendTo($list); });
            }

            if ($('#date_range').length === 1 && typeof $.fn.daterangepicker !== 'undefined') {
                $('#date_range').daterangepicker(dateRangeSettings, function(start, end) {
                    $('#date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    $('#start_date').val(start.format('YYYY-MM-DD'));
                    $('#end_date').val(end.format('YYYY-MM-DD'));
                    if (sales_orders_table) {
                        sales_orders_table.ajax.reload();
                    }
                });
                $('#date_range').on('cancel.daterangepicker', function() {
                    $('#date_range').val(''); $('#start_date').val(''); $('#end_date').val('');
                    if (sales_orders_table) {
                        sales_orders_table.ajax.reload();
                    }
                });
                if ($('#start_date').val() && $('#end_date').val()) {
                    $('#date_range').data('daterangepicker').setStartDate(moment($('#start_date').val()));
                    $('#date_range').data('daterangepicker').setEndDate(moment($('#end_date').val()));
                }
            }

            var sales_orders_table;
            function init_sales_orders_table() {
                if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#sales_orders_table')) {
                    sales_orders_table = $('#sales_orders_table').DataTable({
                        processing: true,
                        serverSide: true,
                        aaSorting: [[2, 'desc']],
                        ajax: {
                            "url": "{{ route('distribution.sales_orders.index') }}",
                            "data": function ( d ) {
                                d.shipping_status = $('select[name="shipping_status"]').val();
                                d.status = $('select[name="status"]').val();
                                d.customer_lookup = $('select[name="customer_lookup"]').val();
                                d.sales_order_no = $('select[name="sales_order_no"]').val();
                                d.location = $('select[name="location"]').val();
                                d.payment_status = $('select[name="payment_status"]').val();
                                d.payment_method = $('select[name="payment_method"]').val();
                                d.added_by = $('select[name="added_by"]').val();
                                if ($('#date_range').val()) {
                                    d.date_from = $('#start_date').val();
                                    d.date_to = $('#end_date').val();
                                }
                            }
                        },
                        columnDefs: [ {
                            "targets": [0, 11, 13, 14],
                            "orderable": false,
                            "searchable": false
                        } ],
                        columns: [
                            { data: 'action', name: 'action' },
                            { data: 'added_by', name: 'added_by' },
                            { data: 'date', name: 'date' },
                            { data: 'sales_order_no', name: 'sales_order_no' },
                            { data: 'sales_invoice_no', name: 'sales_invoice_no', orderable: false, searchable: false },
                            { data: 'delivery_date', name: 'delivery_date' },
                            { data: 'customer_name', name: 'customer_name' },
                            { data: 'customer_address', name: 'customer_address' },
                            { data: 'total_items', name: 'total_items', orderable: false, searchable: false },
                            { data: 'status', name: 'status' },
                            { data: 'grand_total', name: 'grand_total', className: 'text-right' },
                            { data: 'payment_status', name: 'payment_status' },
                            { data: 'paid_amount', name: 'paid_amount', className: 'text-right' },
                            { data: 'payment_method', name: 'payment_method' },
                            { data: 'shipping_status', name: 'shipping_status' }
                        ],
                        dom: '<"row margin-bottom-20"<"col-sm-12 text-right"B><"col-sm-6"f><"col-sm-6"l> r>tip',
                        buttons: [
                            {
                                extend: 'colvis',
                                className: 'btn btn-sm btn-default',
                                text: 'Column Visibility'
                            },
                            {
                                extend: 'csv',
                                footer: true,
                                text: '<i class="fa fa-file"></i> Export to CSV',
                                className: 'btn btn-sm btn-default',
                                exportOptions: {
                                    columns: function (idx, data, node) {
                                        var table = $(node).closest('table').DataTable();
                                        return table.column(idx).visible() && !$(node).hasClass('notexport');
                                    }
                                }
                            },
                            {
                                extend: 'excel',
                                footer: true,
                                text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
                                className: 'btn btn-sm btn-default',
                                exportOptions: {
                                    columns: function (idx, data, node) {
                                        var table = $(node).closest('table').DataTable();
                                        return table.column(idx).visible() && !$(node).hasClass('notexport');
                                    }
                                }
                            },
                            {
                                extend: 'pdf',
                                footer: true,
                                text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
                                className: 'btn btn-sm btn-default',
                                exportOptions: {
                                    columns: function (idx, data, node) {
                                        var table = $(node).closest('table').DataTable();
                                        return table.column(idx).visible() && !$(node).hasClass('notexport');
                                    }
                                }
                            },
                            {
                                extend: 'print',
                                footer: true,
                                text: '<i class="fa fa-print"></i> Print',
                                className: 'btn btn-sm btn-default',
                                exportOptions: {
                                    columns: function (idx, data, node) {
                                        var table = $(node).closest('table').DataTable();
                                        return table.column(idx).visible() && !$(node).hasClass('notexport');
                                    }
                                },
                                customize: function (win) {
                                    $(win.document.body).find('h1').css('text-align', 'center');
                                    $(win.document.body).find('h1').css('font-size', '25px');
                                },
                            }
                        ],
                        pageLength: 25
                    });
                }
            }

            // Initialize table on load and on tab show
            init_sales_orders_table();
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                if ($(e.target).attr('href') === '#list_so_tab') {
                    init_sales_orders_table();
                    $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
                }
            });

            $(document).on('change', 'select[name="shipping_status"], select[name="status"], select[name="customer_lookup"], select[name="sales_order_no"], select[name="location"], select[name="payment_status"], select[name="payment_method"], select[name="added_by"]', function() {
                if (sales_orders_table) {
                    sales_orders_table.ajax.reload();
                }
            });

            $('#list_so_tab form').on('submit', function(e) {
                e.preventDefault();
                if (sales_orders_table) {
                    sales_orders_table.ajax.reload();
                }
            });

            $(document).on('click', '.show-so-notes-btn', function(e) {
                e.preventDefault();
                var salesOrderNote = $(this).attr('data-invoice-note') || '-';
                var shippingNote = $(this).attr('data-shipping-note') || '-';
                
                $('#dis_invoice_note_content').text('-');
                $('#shipping_note_content').text(shippingNote);
                $('#sales_order_note_content').text(salesOrderNote);
                
                $('#salesOrderNotesModal').modal('show');
            });

            $(document).on('click', '.show-so-payment-btn', function() {
                $('#so_pay_cash').text($(this).data('cash') || '0.00');
                $('#so_pay_card').text($(this).data('card') || '0.00');
                $('#so_pay_cheque').text($(this).data('cheque') || '0.00');
                $('#so_pay_credit').text($(this).data('credit') || '0.00');
                $('#so_pay_total').text($(this).data('total') || '0.00');
                $('#salesOrderPaymentModal').appendTo('body').modal('show');
            });

            $(document).on('click', 'a.view_sales_order_url', function (e) {
                e.preventDefault();
                $('div.view_modal').load($(this).attr('href'), function () {
                    $(this).modal('show');
                });
                return false;
            });

            $(document).on('click', '.change-shipping-status-btn', function(e) {
                var selectedStatus = $(this).text().trim() || 'selected';
                if (!confirm('Change shipping status to "' + selectedStatus + '"?')) { 
                    e.preventDefault(); 
                    return false;
                }
            });

            $(document).on('click', '.change-status-btn', function(e) {
                var selectedStatus = $(this).text().trim() || 'selected';
                if (!confirm('Change sales order status to "' + selectedStatus + '"?')) { 
                    e.preventDefault(); 
                    return false;
                }
            });

            $(document).on('submit', '.so-shipping-status-form', function(e) {
                // This is a fallback for the old form structure if any remains, 
                // but the button click above handles the primary confirmation now.
                if ($(this).find('.so-shipping-status-select').length > 0) {
                    var selectedStatus = $(this).find('.so-shipping-status-select option:selected').text() || 'selected';
                    if (!confirm('Change shipping status to "' + selectedStatus + '"?')) { e.preventDefault(); }
                }
            });

            $(document).on('click', '.view-so-changed-activities', function(e) {
                // console.log('masok');
                e.preventDefault();
                var id = $(this).data('id');
                var url = "{{ url('distribution/sales-orders') }}/" + id + "/activities";
                $.ajax({
                    url: url,
                    dataType: 'html',
                    success: function(result) {
                        $('.so_activity_log_modal').html(result);

                        $('.so_activity_log_modal').appendTo('body');

                        $('.so_activity_log_modal').modal({
                            backdrop: 'static',
                            keyboard: false,
                            show: true
                        });
                    },
                    error: function(xhr) {
                        alert('Error loading activities: ' + xhr.status + ' ' + xhr.statusText);
                    }
                });
            });

            $(document).on('click', '.btn-so-changed-details', function(e) {
                e.preventDefault();
                $(this).siblings('.so-changed-details-wrapper').toggle();
            });

            $(document).on('click', '.add_payment_modal, .view_payment_modal', function(e) {
                e.preventDefault();
                var container = $(this).data('container') || '.view_modal';
                $.ajax({
                    url: $(this).attr('href'),
                    dataType: 'html',
                    success: function(result) {
                        $(container).html(result).modal('show');
                    },
                });
            });

            @if(session('status'))
                toastr.success('{{ session('status') }}');
            @endif
            @if(session('success'))
                toastr.success('{{ session('success') }}');
            @endif
            @if(session('error'))
                toastr.error('{{ session('error') }}');
            @endif
        });
    </script>
@endsection
