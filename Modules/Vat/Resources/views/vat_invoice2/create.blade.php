@extends('layouts.app')
@section('title', __('vat::lang.vat_invoice'))

@php
    $business_id = request()->session()->get('business.id') ?? request()->session()->get('user.business_id') ?? optional(auth()->user())->business_id;
    $tax_rate = \App\TaxRate::where('business_id', $business_id)->first();
    $tax = !empty($tax_rate) ? $tax_rate->amount : 0;
@endphp

@section('content')
    @include('vat::vat_invoice2.partials.nav')
    <style>
        /*
         * MA-002 BUILD STAMP.
         *
         * A CSS custom property the diagnostic reads back with
         * getComputedStyle. If the log reports ma002_css_build as empty, this
         * stylesheet is NOT the one the browser loaded - the file did not
         * reach the server, or the compiled Blade view is stale. No CSS I
         * write can work until that reads 44.
         */
        #issue_bill_customer_form {
            --ma002-build: "49";
        }

        table>tbody>tr>td {
            vertical-align: middle;
        }

        /* S733: Service invoices must not expose Unit Cost or Qty to the user. */
        #issue_customer_bill_add_table.vat2-service-mode .vat2-unit-cost-column,
        #issue_customer_bill_add_table.vat2-service-mode .vat2-qty-column {
            display: none !important;
        }

        /*
         * Keep the Add VAT Invoice-2 controls as normal HTML inputs/selects.
         * Stale Select2 wrappers from the global application initializer can be
         * wider than their own column and sit invisibly above Place of Supply,
         * Product, Qty and Sub Total. Hide those generated layers on this page and
         * keep the real form controls clickable/editable.
         */
        #issue_bill_customer_form .select2-container,
        .route_operations_modal .select2-container {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        /* Product Category/Product must be searchable and scrollable. */
        #issue_bill_customer_form .select2-container.vat2-searchable-container {
            display: inline-block !important;
            visibility: visible !important;
            pointer-events: auto !important;
            width: 100% !important;
            min-height: 42px !important;
            z-index: 6 !important;
        }

        #issue_bill_customer_form .select2-container.vat2-searchable-container .select2-selection--single {
            min-height: 42px !important;
            height: 42px !important;
            padding-top: 5px !important;
        }

        .select2-container--open {
            z-index: 99999 !important;
        }

        .select2-results__options {
            max-height: 300px !important;
            overflow-y: auto !important;
        }

        #final_grand_total_words {
            width: 100% !important;
            min-width: 100% !important;
        }

        #issue_bill_customer_form select.vat2-native-select,
        .route_operations_modal select.vat2-native-select {
            display: block !important;
            width: 100% !important;
            min-height: 42px !important;
            height: 42px !important;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
            position: static !important;
            z-index: auto !important;
            cursor: pointer !important;
            background-color: #fff !important;
        }

        #issue_bill_customer_form .form-group,
        #issue_bill_customer_form td,
        .route_operations_modal .form-group {
            position: relative;
            pointer-events: auto !important;
        }

        /*
         * MA-002 (Issue 5) - FIX DERIVED FROM YOUR LOG, NOT FROM GUESSWORK.
         *
         * The click diagnostic reported this element stack under the pointer
         * when a top-of-form dropdown was clicked:
         *
         *   div.clearfix  >  div.row.payment-row.payment_row
         *                 >  div#payment_rows_div
         *                 >  div.box-body.payment_row
         *                 >  select#prefix_id      <- the control, 5th
         *
         *   sel_found  yes at depth 4
         *   sel_style  pe:auto / pos:static / z:auto / op:1 / vis:visible / 182x42
         *   top_style  pe:auto / pos:static / z:auto / op:1 / vis:visible / 1264x829
         *   default_prevented  no
         *
         * So the select is perfectly healthy - enabled, visible, full size,
         * pointer-events auto - and nothing cancels the event. The payment-row
         * block simply PAINTS ON TOP OF IT. The topmost item is an empty
         * div.clearfix measuring 1264x829, which should be zero height.
         *
         * Two rules fix that:
         *
         *   1. A .clearfix is a pure layout artefact with no content and no
         *      handlers. It must never intercept a click, whatever its size.
         *
         *   2. The real form controls are given their own stacking context
         *      above the payment block, so hit-testing reaches them first.
         *
         * Both are presentation-only. No markup, handler or data path changes.
         */
        #issue_bill_customer_form .clearfix,
        #issue_bill_customer_form .row.payment-row > .clearfix {
            pointer-events: none !important;
        }

        /*
         * MA-002 REVERT TO THE KNOWN-GOOD RULE.
         *
         * Parcel 32 used exactly this rule and you confirmed "most of the
         * dropdowns working". Parcel 34 then removed .form-group and gave the
         * products table its own higher levels (8/9/10). That was a mistake:
         * this page has overlapping boxes, so raising the TABLE above the
         * header controls made the table cover them, and ALL the dropdowns
         * stopped working. My regression.
         *
         * Restored verbatim. .form-group stays in - it is what lifts each
         * control's own box above the overlapping payment block.
         *
         * The product dropdown is deliberately NOT special-cased here. I do
         * not yet know what covers it, and guessing again is what broke the
         * working ones. The v4 diagnostic will name it: click the product
         * dropdown once and the log will report the exact element stack, as
         * it did for prefix_id.
         */
        #issue_bill_customer_form .form-group,
        #issue_bill_customer_form select,
        #issue_bill_customer_form input,
        #issue_bill_customer_form textarea,
        #issue_bill_customer_form .select-ro-btn,
        #issue_bill_customer_form button {
            position: relative;
            z-index: 5;
        }

        /* Keep the payment block strictly below the controls above it. */
        /*
         * MA-002 - PRODUCT DROPDOWN. Fix taken directly from your log.
         *
         * Working select (prefix_id, 19:27:47):
         *     select#prefix_id[227,236 182x42 pos:static z:auto]
         *     div.form-group[227,208 182x70 pos:relative z:5]   <- lifted
         *     sel_found: yes at DEPTH 0        <- select is on top
         *
         * Product select (19:15:15):
         *     div.clearfix[3,-31 1264x829]     <- on top
         *     ...
         *     select.product_id                <- DEPTH 4, buried
         *
         * The difference is the wrapper. Every working control sits in a
         * .form-group, which my rule lifts to z-index 5. The product select
         * sits in a plain <td>, so nothing lifts it.
         *
         * Note the selects themselves are NOT lifted by my rule - an existing
         * rule above (line 40) sets
         *     select.vat2-native-select { position: static !important;
         *                                 z-index: auto !important; }
         * which wins on !important. Both logs confirm it: every select reports
         * pos:static z:auto. So the lifting must be done by the WRAPPER, and
         * for the product row the wrapper is the table cell.
         *
         * z-index 5 - deliberately the SAME level as .form-group, not higher.
         * Parcel 34 used 8/9/10 and the table then covered the header controls,
         * which broke every dropdown. Matching the working level cannot do
         * that.
         */
        #issue_customer_bill_add_table td,
        #issue_customer_bill_add_table th {
            position: relative;
            z-index: 5;
        }

        /*
         * MA-002 - the rule that was actually blocking this.
         *
         * Line 40 of this same stylesheet sets, for EVERY select in the form:
         *
         *     #issue_bill_customer_form select.vat2-native-select {
         *         position: static !important;
         *         z-index:  auto !important;
         *     }
         *
         * That is why the header controls could only ever be lifted by their
         * .form-group WRAPPER, and it is why my previous attempt - putting
         * z-index on the <td> - was not enough on its own: the select inside
         * stays position:static, and a positioned <td> is unreliable as a
         * stacking parent once .table sets border-collapse.
         *
         * This overrides that rule for the products table only, using
         * !important to beat it, and lifts the SELECT ITSELF - the same
         * mechanism that demonstrably works for the header fields.
         *
         * z-index 5 again: identical to .form-group, never higher. Parcel 34
         * used 8/9/10, the table covered the header controls, and every
         * dropdown broke.
         */
        #issue_customer_bill_add_table select.vat2-native-select,
        #issue_customer_bill_add_table td select,
        #issue_customer_bill_add_table td input {
            position: relative !important;
            z-index: 5 !important;
        }

        #issue_bill_customer_form .box-body.payment_row,
        #issue_bill_customer_form #payment_rows_div {
            position: relative;
            z-index: 1;
        }

        /*
         * MA-002 - PRODUCT DROPDOWN, approach that does not rely on z-index.
         *
         * The stacking approach fixed the header controls but has not fixed
         * the product select, so I am not going to keep tuning z-index values.
         *
         * The real situation, straight from your log geometry:
         *
         *     div.box-body.payment_row   [0,-49  1270x988]
         *     div#payment_rows_div       [18,-49 1234x952]
         *     div.row.payment-row        [3,-31  1264x952]
         *     div.clearfix               [3,-31  1264x829]
         *
         * The payment block is roughly 1270x988 and starts at y=-49 - it
         * covers essentially the whole form area, including the products
         * table lower down. Those boxes are mostly EMPTY space; the actual
         * payment inputs occupy a small part of them. But an empty div still
         * absorbs clicks.
         *
         * So instead of trying to out-rank them, the containers are made
         * transparent to the pointer while their real contents stay
         * clickable. This is the standard pattern for exactly this problem:
         *
         *     container  pointer-events: none   -> clicks pass straight through
         *     children   pointer-events: auto   -> inputs still work normally
         *
         * Nothing is moved, resized or re-ranked, and the payment fields
         * themselves are unaffected because the second rule restores them.
         */
        /*
         * MA-002 - NOT scoped to #issue_bill_customer_form, deliberately.
         *
         * Your probe returned payment_block "not present" when queried as
         * "#issue_bill_customer_form .box-body.payment_row", while the click
         * stack proves that element exists. So the payment block is NOT a
         * descendant of the form, and every form-scoped rule I wrote - six
         * attempts of them - never matched it. The probe also showed
         * div.clearfix with pe:auto, confirming nothing had reached it.
         *
         * These class names are specific to this screen, so scoping to the
         * form was never necessary.
         */
        .box-body.payment_row,
        #payment_rows_div,
        .row.payment-row,
        div.payment_row {
            pointer-events: none !important;
        }

        /*
         * NOTE: the "> *" selectors were removed here for the same reason the
         * JavaScript children() loop was removed - they restored
         * pointer-events on div.clearfix, which is a direct child of
         * .row.payment-row and is exactly the element blocking the click.
         * Only real controls are restored now.
         */
        .box-body.payment_row input,
        .box-body.payment_row select,
        .box-body.payment_row textarea,
        .box-body.payment_row button,
        .box-body.payment_row a,
        .box-body.payment_row label,
        .box-body.payment_row .form-group {
            pointer-events: auto !important;
        }

        /* The clearfix has no content at all - it must never take a click. */
        .row.payment-row > .clearfix,
        .box-body.payment_row .clearfix,
        #payment_rows_div .clearfix {
            pointer-events: none !important;
            height: 0 !important;
            min-height: 0 !important;
            max-height: 0 !important;
            overflow: hidden !important;
        }

        #issue_bill_customer_form #place_of_supply,
        #issue_bill_customer_form .qty,
        #issue_bill_customer_form .sub_total {
            position: relative !important;
            z-index: 2 !important;
            pointer-events: auto !important;
            user-select: text !important;
            -webkit-user-select: text !important;
            background-color: #fff !important;
        }
    </style>

    <div class="col-md-12">
        {!! Form::open(['method' => 'post', 'id' => 'issue_bill_customer_form']) !!}
        <div class="row">

            <input type="hidden" id="tax_rate" value="{{ $tax }}">
            <input type="hidden" id="route_operation_id" name="route_operation_id">

            <div class="col-md-12" style="margin-top: 20px;">
                <button type="button" class="btn btn-danger pull-right btn-modal"
                    data-href="{{ action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@customerQuickAdd') }}"
                    data-container=".contact_modal">
                    @lang('vat::lang.first_time_customer')
                </button>

                @if ($fleet_active && !empty($fleet_customers))
                    <button type="button" class="btn btn-info pull-left select-ro-btn hide">
                        @lang('vat::lang.select_ro')
                    </button>
                @endif

            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('location_id', __('vat::lang.location')) !!}
                    {!! Form::select('location_id', $business_locations, null, [
                        'class' => 'form-control vat2-native-select',
                        'style' => 'width:100%;',
                        'required',
                    ]) !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('prefix', __('vat::lang.prefix')) !!}
                    {!! Form::select('prefix_id', $prefixes, null, [
                        'class' => 'form-control vat2-native-select',
                        'style' => 'width:100%;',
                        'id' => 'prefix_id',
                        'required',
                    ]) !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('customer_bill_no_display', __('vat::lang.bill_no')) !!}
                    {!! Form::text('customer_bill_no_display', 'Generated on Save', [
                        'class' => 'form-control',
                        'disabled' => true,
                    ]) !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('customer_id', __('vat::lang.customer')) !!}
                    {!! Form::select('customer_id', $customers, null, [
                        'class' => 'form-control vat2-native-select',
                        'style' => 'width:100%;',
                        'required',
                        'placeholder' => __('vat::lang.please_select'),
                    ]) !!}
                </div>
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('vat_number', 'Customer VAT No' . '') !!}
                <div class="input-group">
                    <div class="input-group-btn">
                        <button type="button" class="btn btn-default bg-white btn-flat"
                            title="{{ 'Customer VAT No' }}">
                            <i class="fa fa-user"></i>
                        </button>
                    </div>
                    {!! Form::text('vat_number', null, [
                        'class' => 'form-control',
                        'id' => 'customer_vat_number',
                        'readonly',
                        'placeholder' => 'Customer VAT No',
                    ]) !!}
                    <input type="hidden" id="vat_btn_input">
                    <span class="input-group-btn vat-btn-group hide">
                        <button type="button" class="btn btn-default bg-white btn-flat btn-vat-modal vat-btn-group-action"
                            data-href="" data-container=".contact_modal_noreload">
                            <i class="fa fa-plus-circle text-primary fa-lg"></i>
                        </button>
                    </span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('sub_customer', __('vat::lang.sub_customer')) !!}
                    {!! Form::select('sub_customer', [], null, [
                        'class' => 'form-control vat2-native-select',
                        'style' => 'width:100%;',
                        'placeholder' => __('vat::lang.please_select'),
                    ]) !!}
                </div>
            </div>
        </div>


        <div class="row">

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('invoice_to', __('vat::lang.invoice_to')) !!}
                    {!! Form::select(
                        'invoice_to',
                        ['customer' => __('vat::lang.customer'), 'sub_customer' => __('vat::lang.sub_customer')],
                        null,
                        ['class' => 'form-control vat2-native-select', 'style' => 'width:100%;'],
                    ) !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('voucher_order_creditlimit', __('vat::lang.credit_limit')) !!}
                    {!! Form::text('voucher_order_creditlimit', null, [
                        'class' => 'form-control',
                        'required',
                        'readonly',
                        'placeholder' => __('vat::lang.credit_limit'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('voucher_order_outstanding', __('vat::lang.outstanding')) !!}
                    {!! Form::text('voucher_order_outstanding', null, [
                        'class' => 'form-control',
                        'required',
                        'readonly',
                        'placeholder' => __('vat::lang.outstanding'),
                    ]) !!}
                </div>
            </div>


            <div class="form-group col-sm-2">
                {!! Form::label('reference_id', __('vat::lang.reference')) !!}
                <div class="input-group">
                    <div class="input-group-btn">
                        <button type="button" class="btn btn-default bg-white btn-flat" style=""
                            title="{{ __('airline::lang.customer') }}">
                            <i class="fa fa-user"></i>
                        </button>
                    </div>
                    {!! Form::select('reference_id', [], null, [
                        'class' => 'form-control vat2-native-select',
                        'style' => 'width:100%;',
                        'placeholder' => __('vat::lang.please_select'),
                    ]) !!}
                    <span class="input-group-btn">
                        <button type="button" style=""
                            class="btn btn-default bg-white btn-flat btn-modal  reference-btn"
                            data-href="{{ action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@referenceQuickAdd') }}"
                            data-container=".contact_modal">
                            <i class="fa fa-plus-circle text-primary fa-lg"></i>
                        </button>
                    </span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('sale_type', __('vat::lang.sale_type')) !!}
                    {!! Form::select(
                        'sale_type',
                        ['Product' => __('vat::lang.product'), 'Service' => __('vat::lang.service')],
                        null,
                        [
                            'class' => 'form-control vat2-native-select',
                            'style' => 'width:100%;',
                            'required',
                            'placeholder' => __('vat::lang.please_select'),
                        ],
                    ) !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('voucher_order_amount', __('vat::lang.invoice_amount')) !!}
                    {!! Form::text('voucher_order_amount', null, [
                        'class' => 'form-control',
                        'readonly',
                        'required',
                        'placeholder' => __('vat::lang.invoice_amount'),
                    ]) !!}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('voucher_order_date', __('vat::lang.transaction_date')) !!}
                    {!! Form::text('voucher_order_date', null, [
                        'class' => 'form-control',
                        'readonly',
                        'placeholder' => __('vat::lang.transaction_date'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('supplied_on', __('vat::lang.supplied_on')) !!}
                    {!! Form::date('supplied_on', \Carbon\Carbon::now()->format('Y-m-d'), ['class' => 'form-control', 'placeholder' => __('vat::lang.supplied_on')]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('place_of_supply', __('vat::lang.place_of_supply')) !!}
                    {!! Form::text('place_of_supply', null, [
                        'class' => 'form-control vat2-editable-field',
                        'id' => 'place_of_supply',
                        'placeholder' => __('vat::lang.place_of_supply'),
                        'autocomplete' => 'off',
                    ]) !!}
                </div>
            </div>
                
           <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('print_format', __('vat::lang.print_format')) !!}

                    @php
                        // Show all available formats; controller will handle routing
                        $print_options = [
                            'vat_print_2026' => __('vat::lang.vat_print_2026'),
                            'vat_print_163' => __('vat::lang.vat_print_163'),
                            'old' => __('vat::lang.old_vat_print'),
                        ];
                    @endphp

                    {!! Form::select(
                        'print_format',
                        $print_options,
                        'vat_print_2026',
                        ['class' => 'form-control vat2-native-select', 'style' => 'width:100%;']
                    ) !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('product_category_id', __('vat::lang.product_category')) !!}
                    {!! Form::select('product_category_id', $product_categories, null, [
                        'id' => 'product_category_id',
                        'class' => 'form-control vat2-searchable-select vat2-product-category-select',
                        'style' => 'width:100%;',
                        'placeholder' => __('vat::lang.please_select'),
                    ]) !!}
                </div>
            </div>
        </div>


        <div class="clearfix"></div>

        <table class="table table-responsive" id="issue_customer_bill_add_table">
            <thead>
                <tr>
                    <th width="20%">@lang('vat::lang.product')</th>
                    <th class="vat2-unit-cost-column">Total Before Tax</th>
                    <th class="vat2-qty-column">@lang('vat::lang.qty')</th>
                    <th>@lang('vat::lang.unit_discount')</th>
                    <th>@lang('vat::lang.unit_vat') {{ $tax }} %</th>
                    <th>@lang('vat::lang.vat')</th>
                    <th>@lang('vat::lang.sub_total')</th>
                    <th>@lang('vat::lang.action')</th>
                </tr>
            </thead>
            <tbody>
                <tr class="first-row">
                    <td>
                        {!! Form::select('issue_customer_bill[product_id][]', $products, null, [
                            'class' => 'form-control vat2-searchable-select product_id',
                            'style' => 'width:100%;',
                            'required',
                            'placeholder' => __('vat::lang.please_select'),
                        ]) !!}
                    </td>
                    <td class="vat2-unit-cost-column">
                        {!! Form::hidden('issue_customer_bill[unit_price][]', 0, [
                            'class' => 'form-control unit_price',
                            'placeholder' => __('vat::lang.unit_price'),
                            'readonly',
                        ]) !!}

                        {!! Form::text('issue_customer_bill[unit_price_excl][]', 0, [
                            'class' => 'form-control unit_price_excl',
                            'readonly',
                        ]) !!}
                        {!! Form::hidden('issue_customer_bill[unit_price_unformatted][]', 0, [
                            'class' => 'form-control unit_price_unformatted',
                        ]) !!}
                        {!! Form::hidden('issue_customer_bill[unit_price_excl_unformatted][]', 0, [
                            'class' => 'form-control unit_price_excl_unformatted',
                        ]) !!}

                    </td>
                    <td class="vat2-qty-column">
                        {!! Form::text('issue_customer_bill[qty][]', 0, [
                            'class' => 'form-control qty vat2-editable-field',
                            'placeholder' => __('vat::lang.qty'),
                            'inputmode' => 'decimal',
                            'autocomplete' => 'off',
                        ]) !!}
                    </td>
                    <td>
                        {!! Form::text('issue_customer_bill[discount][]', 0, [
                            'class' => 'form-control discount',
                            'placeholder' => __('vat::lang.discount'),
                        ]) !!}
                    </td>

                    <td>
                        {!! Form::text('issue_customer_bill[unit_vat_rate][]', 0, [
                            'class' => 'form-control unit_vat_rate text-right',
                            'placeholder' => __('vat::lang.unit_vat'),
                            'readonly',
                        ]) !!}
                    </td>

                    <td>
                        {!! Form::text('issue_customer_bill[tax][]', 0, [
                            'class' => 'form-control tax',
                            'readonly',
                            'placeholder' => __('vat::lang.tax'),
                        ]) !!}
                        {!! Form::hidden('issue_customer_bill[tax_unformatted][]', 0, ['class' => 'form-control tax_unformatted']) !!}
                    </td>
                    <td>
                        {!! Form::text('issue_customer_bill[sub_total][]', 0, [
                            'class' => 'form-control sub_total vat2-editable-field',
                            'placeholder' => __('vat::lang.sub_total'),
                            'inputmode' => 'decimal',
                            'autocomplete' => 'off',
                        ]) !!}
                        {!! Form::hidden('issue_customer_bill[sub_total_unformatted][]', 0, [
                            'class' => 'form-control sub_total_unformatted',
                        ]) !!}
                    </td>
                    <td>
                        <button type="button" class="btn btn-xs btn-primary add_row" style="margin-top: 6px;">+</button>
                    </td>
                </tr>

            </tbody>
            <tfoot>
                <tr>
                    <th class="vat2-summary-spacer" colspan="4"></th>
                    <th class="vat2-summary-label" colspan="2">
                        @lang('vat::lang.total_invoice_amount_with_vat')
                    </th>
                    <th>
                        {!! Form::text('grand_total', 0, [
                            'class' => 'form-control',
                            'readonly',
                            'id' => 'grand_total',
                            'placeholder' => __('vat::lang.total'),
                        ]) !!}
                    </th>
                    <th></th>
                </tr>

                <tr>
                    <th class="vat2-summary-spacer" colspan="4"></th>
                    <th class="vat2-summary-label" colspan="2">
                        @lang('vat::lang.tax_base_value')
                    </th>
                    <th>
                        {!! Form::text('grand_total', 0, [
                            'class' => 'form-control',
                            'readonly',
                            'id' => 'grand_total_with_vat',
                            'placeholder' => __('vat::lang.total'),
                        ]) !!}
                    </th>
                    <th></th>
                </tr>

                <tr>
                    <th class="vat2-summary-spacer" colspan="4"></th>
                    <th class="vat2-summary-label" colspan="2">
                        @lang('vat::lang.vat') ({{ $tax }}%)

                    </th>
                    <th>
                        {!! Form::text('vat_total', 0, [
                            'class' => 'form-control',
                            'readonly',
                            'id' => 'vat_total',
                            'placeholder' => __('vat::lang.total'),
                        ]) !!}
                    </th>
                    <th></th>
                </tr>


                <tr>
                    <th class="vat2-summary-spacer" colspan="4"></th>
                    <th class="vat2-summary-label" colspan="2">
                        @lang('vat::lang.price_adjustment')
                    </th>
                    <th>
                        {!! Form::text('price_adjustment', @num_format(0), [
                            'class' => 'form-control',
                            'id' => 'price_adjustment',
                            'placeholder' => __('vat::lang.price_adjustment'),
                        ]) !!}
                    </th>
                    <th></th>
                </tr>


                <tr>
                    <th class="vat2-summary-spacer" colspan="4"></th>
                    <th class="vat2-summary-label" colspan="2">
                        @lang('vat::lang.total_invoice_amount_with_vat')
                    </th>
                    <th>
                        {!! Form::text('final_grand_total', 0, [
                            'class' => 'form-control',
                            'readonly',
                            'id' => 'final_grand_total_with_vat',
                            'placeholder' => __('vat::lang.total'),
                        ]) !!}
                    </th>
                    <th></th>
                </tr>
                <tr class="vat2-amount-words-row">
                    <th class="vat2-amount-words-spacer" colspan="2"></th>
                    <th class="vat2-amount-words-label" colspan="2">
                        @lang('vat::lang.amount_in_words')
                    </th>
                    <th class="vat2-amount-words-value" colspan="3">
                        {!! Form::textarea('final_grand_total_words', null, [
                            'class' => 'form-control vat2-amount-words-input',
                            'id' => 'final_grand_total_words',
                            'rows' => 2,
                            'style' => 'resize:none; overflow:hidden; width:100%; min-height:58px;',
                            'placeholder' => __('vat::lang.amount_in_words'),
                        ]) !!}
                    </th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="box-body payment_row" data-row_id="0">
        <div id="payment_rows_div">
            @include('sale_pos.partials.payment_row_form', ['row_index' => 0])
           
        </div>
    </div>
    <div class="col-12" style="padding:10px;">
        <div class="form-group">
            {!! Form::label('additional_information', __('sale.additional_information_if') . ':') !!}
            {!! Form::textarea('additional_information', null, [
                'class' => 'form-control',
                'rows' => 3,
                'id' => 'additional_information'
            ]) !!}
        </div>
    </div>
     <hr>

    <div class="pull-right">
        <button type="submit" class="btn btn-primary" id="save_issue_bill_customer_btn"
            formaction="{{ action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@store') }}">@lang('messages.save')</button>
        <button type="submit" class="btn btn-danger" id="save_issue_bill_customer_btn"
            formaction="{{ action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@store') }}?is_print=true">@lang('messages.save_and_print')</button>
    </div>

    {!! Form::close() !!}

    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade contact_modal_noreload" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    </div>

    <div class="modal fade route_operations_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">

        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">@lang('vat::lang.select_ro')</h4>
                </div>

                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('ro_customer_id', __('vat::lang.customer')) !!}
                                {!! Form::select('ro_customer_id', $fleet_customers, null, [
                                    'class' => 'form-control vat2-native-select',
                                    'style' => 'width:100%;',
                                    'required',
                                    'placeholder' => __('vat::lang.please_select'),
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('ro_id', __('vat::lang.route_operation')) !!}
                                {!! Form::select('ro_id', [], null, [
                                    'class' => 'form-control vat2-native-select',
                                    'style' => 'width:100%;',
                                    'required',
                                    'placeholder' => __('vat::lang.please_select'),
                                ]) !!}
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="update_ro" data-dismiss="modal"
                        disabled>@lang('messages.save')</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>

            </div><!-- /.modal-content -->
        </div>
    </div>

@endsection
@section('javascript')
    <script>
    /*
     * MA-002 - PRODUCT DROPDOWN, JavaScript fallback.
     *
     * Three CSS approaches have not fixed this. Inline styles set from
     * JavaScript beat every stylesheet regardless of load order, specificity
     * or !important, so this does not depend on which CSS rule wins.
     *
     * From your log, the click on the product select lands on a stack of
     * containers that sit on top of it:
     *
     *     div.clearfix               [3,-31  1264x829]
     *     div.row.payment-row        [3,-31  1264x952]
     *     div#payment_rows_div       [18,-49 1234x952]
     *     div.box-body.payment_row   [0,-49  1270x988]
     *
     * Those boxes are almost entirely EMPTY - the payment inputs occupy a
     * small part - but an empty div still absorbs a click.
     *
     * This walks the form after load and, for each of those containers,
     * sets pointer-events:none inline while restoring pointer-events:auto
     * inline on every element inside that can actually be interacted with.
     * Clicks pass through the empty space; the payment fields keep working.
     *
     * It also collapses any genuinely empty .clearfix to zero height, since
     * one of them is reported as 829px tall when it should be 0.
     *
     * Runs on load and again after 400ms and 1200ms, so anything that
     * re-renders the payment rows afterwards is covered too.
     */
    (function ($) {
        'use strict';
        if (!$) { return; }

        /*
         * MA-002 - NOT scoped to the form. Your probe proved the payment
         * block is not a descendant of #issue_bill_customer_form, so every
         * form-scoped selector I used never matched it.
         */
        var CONTAINERS = [
            '.box-body.payment_row',
            '#payment_rows_div',
            '.row.payment-row',
            'div.payment_row'
        ].join(',');

        var INTERACTIVE = 'input, select, textarea, button, a, label, .btn, .form-group, [onclick]';

        function apply() {
            try {
                $('.box-body.payment_row .clearfix, #payment_rows_div .clearfix, .row.payment-row > .clearfix').each(function () {
                    if (!this.children.length && !$.trim(this.textContent || '')) {
                        this.style.setProperty('height', '0', 'important');
                        this.style.setProperty('min-height', '0', 'important');
                        this.style.setProperty('max-height', '0', 'important');
                        this.style.setProperty('overflow', 'hidden', 'important');
                        this.style.setProperty('pointer-events', 'none', 'important');
                    }
                });

                $(CONTAINERS).each(function () {
                    this.style.setProperty('pointer-events', 'none', 'important');

                    /*
                     * Restore ONLY the interactive elements.
                     *
                     * The previous revision also restored every direct child.
                     * That was a bug of mine: div.clearfix is a direct child
                     * of .row.payment-row, so restoring all children set
                     * pointer-events back to auto on the very element that
                     * blocks the click - undoing the fix completely.
                     *
                     * pointer-events:auto on a descendant works even when an
                     * ancestor is none, so restoring the controls alone is
                     * both sufficient and safe.
                     */
                    $(this).find(INTERACTIVE).each(function () {
                        this.style.setProperty('pointer-events', 'auto', 'important');
                    });
                });

                // Belt and braces: any empty layout div inside the payment
                // block must never take a click, whatever else has run.
                $(CONTAINERS).find('div').each(function () {
                    if (!this.children.length && !$.trim(this.textContent || '')) {
                        this.style.setProperty('pointer-events', 'none', 'important');
                        this.style.setProperty('height', '0', 'important');
                        this.style.setProperty('min-height', '0', 'important');
                    }
                });

                // The products table and its controls must always take clicks.
                $('#issue_customer_bill_add_table').find('td, th, select, input').each(function () {
                    this.style.setProperty('pointer-events', 'auto', 'important');
                });
            } catch (ignore) {}
        }

        $(function () {
            apply();
            window.setTimeout(apply, 400);
            window.setTimeout(apply, 1200);
            $(document).on('ajaxComplete', function () { window.setTimeout(apply, 50); });
        });
    }(window.jQuery));
    </script>
    @include('vat::partials.ma002_click_diagnostic')
    @include('vat::partials.ma002_modal_backdrop_guard')
    <script src="{{ url('Modules/Vat/Resources/assets/js/app-new.js') }}"></script>
    <script>
        (function($, window, document) {
            'use strict';

            window.vatInvoice2PleaseSelect = {!! json_encode(__('vat::lang.please_select')) !!};

            function collectVatInvoice2Selects(scope) {
                var $scope = scope ? $(scope) : $(document);

                if ($scope.is('select')) {
                    return $scope;
                }

                if ($scope[0] === document) {
                    return $('#issue_bill_customer_form select, .route_operations_modal select');
                }

                return $scope.find('select').addBack('select').filter(function() {
                    return $(this).closest('#issue_bill_customer_form, .route_operations_modal').length > 0;
                });
            }

            /*
             * Restore the real HTML <select> and remove every generated Select2
             * wrapper. This runs immediately, before the application's global
             * document-ready Select2 initializer, so the global code cannot claim
             * these controls again.
             */
            window.vatInvoice2UseNativeSelects = function(scope) {
                collectVatInvoice2Selects(scope).each(function() {
                    var $select = $(this);

                    // Product Category and Product use Select2 intentionally for
                    // type-to-filter, keyboard up/down navigation and scrolling.
                    if ($select.hasClass('vat2-searchable-select')) {
                        return;
                    }

                    try {
                        if ($.fn.select2 &&
                            ($select.hasClass('select2-hidden-accessible') || $select.data('select2'))) {
                            $select.select2('destroy');
                        }
                    } catch (ignore) {
                        // A stale generated wrapper is removed explicitly below.
                    }

                    $select.siblings('.select2-container').remove();
                    $select
                        .removeClass('select2 select2-hidden-accessible')
                        .addClass('vat2-native-select')
                        .removeAttr('aria-hidden data-select2-id tabindex disabled')
                        .prop('disabled', false)
                        .removeData('select2')
                        .css({
                            display: 'block',
                            width: '100%',
                            minHeight: '42px',
                            height: '42px',
                            opacity: 1,
                            visibility: 'visible',
                            pointerEvents: 'auto',
                            position: 'static',
                            zIndex: 'auto'
                        });

                    $select.find('option').removeAttr('data-select2-id');
                });

                $('#issue_bill_customer_form .select2-container:not(.vat2-searchable-container), .route_operations_modal .select2-container').remove();
            };

            window.vatInvoice2InitSearchableSelects = function(scope) {
                var $scope = scope ? $(scope) : $('#issue_bill_customer_form');
                var $selects = $scope.find('select.vat2-searchable-select').addBack('select.vat2-searchable-select');

                if (!$.fn || typeof $.fn.select2 !== 'function') {
                    return;
                }

                $selects.each(function() {
                    var $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible') || $select.data('select2')) {
                        $select.next('.select2-container').addClass('vat2-searchable-container');
                        return;
                    }

                    $select.select2({
                        width: '100%',
                        placeholder: window.vatInvoice2PleaseSelect,
                        allowClear: true,
                        minimumResultsForSearch: 0
                    });
                    $select.next('.select2-container').addClass('vat2-searchable-container');
                });
            };

            window.vatInvoice2ReleaseEditableFields = function(scope) {
                var $scope = scope ? $(scope) : $('#issue_bill_customer_form');
                var $fields = $scope.find('#place_of_supply, .qty, .sub_total')
                    .addBack('#place_of_supply, .qty, .sub_total');

                $fields
                    .prop('readonly', false)
                    .prop('disabled', false)
                    .removeAttr('readonly disabled aria-disabled')
                    .css({
                        pointerEvents: 'auto',
                        position: 'relative',
                        zIndex: 2,
                        backgroundColor: '#fff'
                    });
            };

            window.vatInvoice2RestoreControls = function(scope) {
                window.vatInvoice2UseNativeSelects(scope || document);
                window.vatInvoice2ReleaseEditableFields(scope || $('#issue_bill_customer_form'));
                window.vatInvoice2InitSearchableSelects(scope || $('#issue_bill_customer_form'));
            };

            // Restore all controls immediately and again after any delayed global
            // Select2/readonly manipulation from the main application scripts.
            window.vatInvoice2RestoreControls(document);

            $(function() {
                window.vatInvoice2RestoreControls(document);

                if ($.fn && typeof $.fn.datepicker === 'function') {
                    /*
                     * S664 (item 11): the Date column on List VAT Invoice 2 was
                     * empty for saved invoices.
                     *
                     * This called .datepicker('setDate', ...) on a field the
                     * picker had never been INITIALISED on. Depending on the
                     * picker build that either does nothing - leaving the input
                     * blank - or attaches one with default settings whose output
                     * format the server cannot parse. Either way
                     * Carbon::parse($request->voucher_order_date) in store()
                     * received nothing usable and vat_invoices_2.date was saved
                     * empty, which is why the column renders blank.
                     *
                     * Initialising first, with the format the rest of the module
                     * uses, means the field always holds a value the server can
                     * read back.
                     */
                    $('#voucher_order_date').datepicker({
                        format: (typeof datepicker_date_format !== 'undefined' && datepicker_date_format)
                            ? datepicker_date_format
                            : 'mm/dd/yyyy',
                        autoclose: true,
                        todayHighlight: true
                    }).datepicker('setDate', new Date());
                }

                $('.reference-btn').hide();

                var formNode = document.getElementById('issue_bill_customer_form');
                if (formNode && window.MutationObserver) {
                    var restoreScheduled = false;
                    var observer = new MutationObserver(function() {
                        if (restoreScheduled) {
                            return;
                        }
                        restoreScheduled = true;
                        window.setTimeout(function() {
                            restoreScheduled = false;
                            window.vatInvoice2RestoreControls(formNode);
                        }, 0);
                    });
                    observer.observe(formNode, {
                        childList: true,
                        subtree: true
                    });

                    [100, 500, 1500].forEach(function(delay) {
                        window.setTimeout(function() {
                            window.vatInvoice2RestoreControls(formNode);
                        }, delay);
                    });
                }
            });

            $('.route_operations_modal')
                .off('shown.bs.modal.vatInvoice2Native')
                .on('shown.bs.modal.vatInvoice2Native', function() {
                    window.vatInvoice2UseNativeSelects(this);
                });
        })(jQuery, window, document);



        $(document).on('click', '.select-ro-btn', function() {
            $(".route_operations_modal").modal('show');
        })

        $(document).on('click', '#update_vat_number', function(e) {
            e.preventDefault();

            if ($("#update_fields_type").val() == 'nic_number') {
                var data = {
                    'nic_number': $("#add_nic_number").val()
                };
            } else if ($("#update_fields_type").val() == 'mobile') {
                var data = {
                    'mobile': $("#add_mobile").val()
                };
            } else {
                if ($("#is_single_field").val() == 'yes') {
                    var data = {
                        'vat_number': $("#main_add_vat_number").val()
                    };
                } else {
                    var data = {
                        'vat_number': $("#add_vat_number").val()
                    };
                }

            }


            $.ajax({
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: data,
                url: $('#contact_vat_number_form').attr('action'),
                success: function(result) {
                    if (result.success == true) {
                        $('div.contact_modal_noreload').modal('hide');
                        toastr.success(result.msg);

                        if ($("#update_fields_type").val() == 'nic_number') {
                            $("#passport_number_text").val(result.contact.nic_number);
                        } else if ($("#update_fields_type").val() == 'mobile') {
                            $("#passenger_mobile_text").val(result.contact.mobile);
                        } else {
                            $("#customer_vat_number").val(result.contact.vat_number);
                        }

                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', '.btn-vat-modal', function(e) {

            e.preventDefault();

            var url = '/contacts/update-vatnumber/' + $("#vat_btn_input").val();


            console.log(url);

            var container = $(this).data('container');

            $(container).empty();

            $.ajax({

                url: url,

                dataType: 'html',

                success: function(result) {
                    // var contact = $('#default_contact_id').val();
                    $(container).html(result).modal('show');
                    // $(container).find('input#contact_id').val(contact);
                },

            });

        });

        $('#customer_id').change(function() {
            var customer_id = $(this).val();
            var $subCustomer = $('#sub_customer');
            var $reference = $('#reference_id');
            var pleaseSelect = window.vatInvoice2PleaseSelect || 'Please Select';

            $subCustomer.empty().append(new Option(pleaseSelect, '', true, false));
            $reference.empty().append(new Option(pleaseSelect, '', true, false));
            window.vatInvoice2UseNativeSelects($subCustomer);
            window.vatInvoice2UseNativeSelects($reference);

            if (!customer_id) {
                $('.reference-btn').hide();
                $('.vat-btn-group').addClass('hide');
                $('#vat_btn_input').val('');
                $('#customer_vat_number').val('');
                $('#voucher_order_outstanding').val('');
                $('#voucher_order_creditlimit').val('');
                return;
            }

            $('.reference-btn').show();
            $('.vat-btn-group').removeClass('hide');
            $('#vat_btn_input').val(customer_id);

            $.ajax({
                method: 'GET',
                url: '/vat-module/vat-invoice2/customer-details/' + encodeURIComponent(customer_id),
                dataType: 'json',
                global: false,
                success: function(result) {
                    result = result || {};

                    $.each(result.sub_customers || {}, function(id, name) {
                        $subCustomer.append(new Option(name, id, false, false));
                    });

                    $.each(result.references || {}, function(id, name) {
                        $reference.append(new Option(name, id, false, false));
                    });

                    if (typeof __write_number === 'function') {
                        __write_number($('#voucher_order_outstanding'), parseFloat(result.total_outstanding || 0));
                        __write_number($('#voucher_order_creditlimit'), parseFloat(result.credit_limit || 0));
                    } else {
                        $('#voucher_order_outstanding').val(result.total_outstanding || 0);
                        $('#voucher_order_creditlimit').val(result.credit_limit || 0);
                    }

                    // Load the linked Customer VAT No immediately after selection.
                    $('#customer_vat_number').val(result.vat_number || '');
                    $('#voucher_order_amount').trigger('change');
                    window.vatInvoice2UseNativeSelects($subCustomer);
                    window.vatInvoice2UseNativeSelects($reference);
                },
                error: function() {
                    // Keep the form stable and suppress the application-wide AJAX
                    // error toast. A missing customer must never break the page.
                    $('#customer_vat_number').val('');
                    $('#voucher_order_outstanding').val(0);
                    $('#voucher_order_creditlimit').val(0);
                    window.vatInvoice2UseNativeSelects($subCustomer);
                    window.vatInvoice2UseNativeSelects($reference);
                }
            });
        });


        $('#ro_customer_id').change(function() {
            let customer_id = $('#ro_customer_id :selected').val();
            $("#update_ro").attr('disabled', true);

            let $select = $('#ro_id');
            $select.empty().append('<option value="">Please select</option>');

            if (customer_id) {
                $.ajax({
                    method: "get",
                    url: "/vat-module/get-route-ops/" + customer_id,
                    data: {},
                    success: function(result) {

                        for (const [key, value] of Object.entries(result)) {
                            let $option = $('<option>', {
                                value: key,
                                text: value
                            });
                            $select.append($option);
                        }
                        window.vatInvoice2UseNativeSelects($select);
                    },
                });
            }

        })

        $('#ro_id').change(function() {
            let customer_id = $('#ro_id :selected').val();
            $("#update_ro").attr('disabled', true);

            if (customer_id) {
                $.ajax({
                    method: "get",
                    url: "/vat-module/get-ro-details/" + customer_id,
                    data: {},
                    success: function(result) {

                        $("#customer_id").val(result.contact_id).trigger('change');
                        $("#sale_type").val('Product').trigger('change');
                        // Route operations already define their product lines. Clear the
                        // optional category filter so those saved products remain available.
                        $("#product_category_id").val('').trigger('change');
                        __write_number($('#voucher_order_amount'), result.amount);
                        $("#voucher_order_date").val(result.date_of_operation);
                        $("#supplied_on").val(result.date_of_operation);
                        $("#route_operation_id").val(result.id);

                        $("#update_ro").attr('disabled', false);

                        var products_arr;
                        var qty_arr;

                        try {
                            products_arr = (typeof result.product_id === 'string') ? JSON.parse(result.product_id) : result.product_id;
                        } catch (e) {
                            console.error('Failed to parse product_id:', e, result.product_id);
                            products_arr = [];
                        }

                        try {
                            qty_arr = (typeof result.qty === 'string') ? JSON.parse(result.qty) : result.qty;
                        } catch (e) {
                            console.error('Failed to parse qty:', e, result.qty);
                            qty_arr = [];
                        }

                        products_arr = Array.isArray(products_arr) ? products_arr : [];
                        qty_arr = Array.isArray(qty_arr) ? qty_arr : [];

                        var $tbody = $('#issue_customer_bill_add_table tbody');
                        var $firstRow = $tbody.find('tr.first-row').first();
                        $tbody.find('tr').not($firstRow).remove();

                        for (var i = 1; i < products_arr.length; i++) {
                            if (window.vatInvoice2NewRowHtml) {
                                var $routeRow = $(window.vatInvoice2NewRowHtml);
                                $tbody.append($routeRow);
                                window.vatInvoice2UseNativeSelects($routeRow);
                                window.vatInvoice2InitSearchableSelects($routeRow);
                            }
                        }

                        $tbody.find('tr').each(function(index) {
                            if (typeof products_arr[index] === 'undefined') {
                                return false;
                            }

                            $(this).find('.product_id').val(products_arr[index]).trigger('change');
                            $(this).find('.qty').val(qty_arr[index] || 0).trigger('change');
                        });
                    },
                });
            }

        })


        // customer_bill_no is now generated on the backend during save



        $(document).ready(function() {
            /*
             * S733: Product keeps the existing table. Service removes Unit Cost and Qty
             * from the visible form immediately, including dynamically-added rows. Values
             * stay in the DOM so the existing VAT calculations/save path are not disturbed.
             */
            function applyVatInvoice2SaleTypeLayout() {
                var isService = String($('#sale_type').val() || '').trim().toLowerCase() === 'service';
                var $table = $('#issue_customer_bill_add_table');

                $table.toggleClass('vat2-service-mode', isService);
                $table.find('tfoot .vat2-summary-spacer').attr('colspan', isService ? 2 : 4);
                $table.find('tfoot .vat2-amount-words-spacer').attr('colspan', isService ? 1 : 2);
                $table.find('tfoot .vat2-amount-words-label').attr('colspan', isService ? 1 : 2);
            }

            $(document).on('change.vatInvoice2SaleType', '#sale_type', applyVatInvoice2SaleTypeLayout);
            applyVatInvoice2SaleTypeLayout();

            var vatInvoice2ProductCatalog = {!! json_encode($products->map(function ($name, $id) { return ['id' => (string) $id, 'name' => $name]; })->values()) !!};
            var vatInvoice2ProductCategoryLinks = {!! json_encode($product_category_links) !!};

            function productMatchesVatInvoice2Category(productId, categoryId) {
                if (!categoryId) {
                    return true;
                }
                var links = vatInvoice2ProductCategoryLinks[String(productId)] || [];
                return links.map(String).indexOf(String(categoryId)) !== -1;
            }

            function refillVatInvoice2ProductSelect($select, categoryId, preserveSelection) {
                var currentValue = preserveSelection ? String($select.val() || '') : '';
                var currentAllowed = currentValue && productMatchesVatInvoice2Category(currentValue, categoryId);

                $select.empty().append($('<option>', { value: '', text: window.vatInvoice2PleaseSelect }));
                $.each(vatInvoice2ProductCatalog, function(index, product) {
                    var productId = String(product.id);
                    if (productMatchesVatInvoice2Category(productId, categoryId)) {
                        $select.append($('<option>', { value: productId, text: product.name }));
                    }
                });

                $select.val(currentAllowed ? currentValue : '');
                $select.trigger('change.select2');

                if (preserveSelection && currentValue && !currentAllowed) {
                    $select.trigger('change');
                }
            }

            function applyVatInvoice2ProductCategoryFilter(scope, preserveSelection) {
                var categoryId = String($('#product_category_id').val() || '');
                var $scope = scope ? $(scope) : $('#issue_customer_bill_add_table');
                $scope.find('select.product_id').addBack('select.product_id').each(function() {
                    refillVatInvoice2ProductSelect($(this), categoryId, preserveSelection !== false);
                });
                window.vatInvoice2InitSearchableSelects($scope);
            }

            $(document).on('change.vatInvoice2Category', '#product_category_id', function() {
                applyVatInvoice2ProductCategoryFilter($('#issue_customer_bill_add_table'), true);
            });

            $(document).on('select2:open', function() {
                window.setTimeout(function() {
                    $('.select2-container--open .select2-search__field').last().trigger('focus');
                }, 0);
            });

            window.vatInvoice2InitSearchableSelects($('#issue_bill_customer_form'));

            let vatInvoice2UnitVatDecimals = 2;
            let vatInvoice2UnitVatRoundingOffRequired = false;
            let vatInvoice2SubTotalDecimals = 2;
            let vatInvoice2SubTotalRoundingOffRequired = false;

            function isEnabledPrefixOption(value) {
                return value === true || value === 1 || value === '1';
            }

            function applyPrefixVatConfig(result) {
                var unitVatDecimals = parseInt(result.unit_vat_no_of_decimals, 10);
                var subTotalDecimals = parseInt(result.sub_total_no_of_decimals, 10);
                vatInvoice2UnitVatDecimals = (!isNaN(unitVatDecimals) && unitVatDecimals >= 0) ? Math.min(unitVatDecimals, 10) : 2;
                vatInvoice2SubTotalDecimals = (!isNaN(subTotalDecimals) && subTotalDecimals >= 0) ? Math.min(subTotalDecimals, 10) : 2;
                vatInvoice2UnitVatRoundingOffRequired = isEnabledPrefixOption(result.unit_vat_rounding_off_required);
                vatInvoice2SubTotalRoundingOffRequired = isEnabledPrefixOption(result.sub_total_rounding_off_required);
            }

            function truncateByDecimals(value, decimals) {
                var factor = Math.pow(10, decimals);
                return value < 0 ? Math.ceil(value * factor) / factor : Math.floor(value * factor) / factor;
            }

            function applyConfiguredDecimals(value, decimals, roundingRequired) {
                var numericValue = parseFloat(value || 0);
                if (isNaN(numericValue)) { numericValue = 0; }
                return roundingRequired
                    ? Number(numericValue.toFixed(decimals))
                    : truncateByDecimals(numericValue, decimals);
            }

            function roundByPrefixUnitVatDecimals(value) {
                return applyConfiguredDecimals(value, vatInvoice2UnitVatDecimals, vatInvoice2UnitVatRoundingOffRequired);
            }

            function roundByPrefixSubTotalDecimals(value) {
                return applyConfiguredDecimals(value, vatInvoice2SubTotalDecimals, vatInvoice2SubTotalRoundingOffRequired);
            }

            function formatByPrefixUnitVatDecimals(value) {
                return __number_f(roundByPrefixUnitVatDecimals(value), false, false, vatInvoice2UnitVatDecimals);
            }

            function writePrefixUnitVatNumber(field, value) {
                var normalised = roundByPrefixUnitVatDecimals(value);
                __write_number(field, normalised, false, vatInvoice2UnitVatDecimals);
                return normalised;
            }

            function writePrefixSubTotalNumber(field, value) {
                var normalised = roundByPrefixSubTotalDecimals(value);
                __write_number(field, normalised, false, vatInvoice2SubTotalDecimals);
                return normalised;
            }

            function recalculateAllRows() {
                $('#issue_customer_bill_add_table tbody tr').each(function() {
                    calculate($(this).find('.qty'));
                });
            }

            $(document).on('change', '#prefix_id', function() {
                let prefix_id = $(this).val();
                if (prefix_id) {
                    $.ajax({
                        method: 'get',
                        url: '/vat-module/get-prefix2/' + prefix_id,
                        data: {},
                        success: function(result) {
                            applyPrefixVatConfig(result);
                            if ($('#customer_bill_no_display').length) {
                                $("#customer_bill_no_display").val(result.bill_no || 'Generated on Save');
                            }
                            recalculateAllRows();
                        },
                    });
                } else {
                    vatInvoice2UnitVatDecimals = 2;
                    vatInvoice2UnitVatRoundingOffRequired = false;
        vatInvoice2SubTotalDecimals = 2;
        vatInvoice2SubTotalRoundingOffRequired = false;
                    if ($('#customer_bill_no_display').length) {
                        $("#customer_bill_no_display").val('Generated on Save');
                    }
                    recalculateAllRows();
                }
            });

            $('#prefix_id').trigger('change');

            var new_row = `
            <tr>
              <td>
                {!! Form::select('issue_customer_bill[product_id][]', $products, null, [
                    'class' => 'form-control vat2-searchable-select product_id',
                    'style' => 'width:100%;',
                    'required',
                    'placeholder' => __('vat::lang.please_select'),
                ]) !!}
              </td>
              <td class="vat2-unit-cost-column">
                {!! Form::hidden('issue_customer_bill[unit_price][]', 0, [
                    'class' => 'form-control unit_price',
                    'placeholder' => __('vat::lang.unit_price'),
                    'readonly',
                ]) !!}
                
                {!! Form::text('issue_customer_bill[unit_price_excl][]', 0, [
                    'class' => 'form-control unit_price_excl',
                    'readonly',
                ]) !!}
                {!! Form::hidden('issue_customer_bill[unit_price_unformatted][]', 0, [
                    'class' => 'form-control unit_price_unformatted',
                ]) !!}
                {!! Form::hidden('issue_customer_bill[unit_price_excl_unformatted][]', 0, [
                    'class' => 'form-control unit_price_excl_unformatted',
                ]) !!}
              </td>
              <td class="vat2-qty-column">
                {!! Form::text('issue_customer_bill[qty][]', 0, [
                    'class' => 'form-control qty vat2-editable-field',
                    'placeholder' => __('vat::lang.qty'),
                    'inputmode' => 'decimal',
                    'autocomplete' => 'off',
                ]) !!}
              </td>
              <td>
                {!! Form::text('issue_customer_bill[discount][]', 0, [
                    'class' => 'form-control discount',
                    'placeholder' => __('vat::lang.discount'),
                ]) !!}
              </td>
              
               <td>
                {!! Form::text('issue_customer_bill[unit_vat_rate][]', 0, [
                    'class' => 'form-control unit_vat_rate text-right',
                    'placeholder' => __('vat::lang.unit_vat'),
                    'readonly',
                ]) !!}
              </td>
              
              <td>
                {!! Form::text('issue_customer_bill[tax][]', 0, [
                    'class' => 'form-control tax',
                    'readonly',
                    'placeholder' => __('vat::lang.tax'),
                ]) !!}
                {!! Form::hidden('issue_customer_bill[tax_unformatted][]', 0, ['class' => 'form-control tax_unformatted']) !!}
              </td>
              <td>
                {!! Form::text('issue_customer_bill[sub_total][]', 0, [
                    'class' => 'form-control sub_total vat2-editable-field',
                    'placeholder' => __('vat::lang.sub_total'),
                    'inputmode' => 'decimal',
                    'autocomplete' => 'off',
                ]) !!}
                {!! Form::hidden('issue_customer_bill[sub_total_unformatted][]', 0, [
                    'class' => 'form-control sub_total_unformatted',
                ]) !!}
              </td>
              <td>
                <button type="button" class="btn btn-xs btn-danger remove_row" style="margin-top: 6px;">-</button>
              </td>
            </tr>
        `;
            window.vatInvoice2NewRowHtml = new_row;

            $(document).on('click', '.add_row', function() {
                var $newRow = $(new_row);
                $('#issue_customer_bill_add_table tbody').prepend($newRow);
                window.vatInvoice2RestoreControls($newRow);
                applyVatInvoice2ProductCategoryFilter($newRow, false);
                applyVatInvoice2SaleTypeLayout();
            });


            $(document).on('mousedown focus click', '#place_of_supply, .qty, .sub_total, .product_id', function() {
                window.vatInvoice2RestoreControls($(this).closest('tr, .form-group'));
            });

            $(document).on('click', '.remove_row', function() {
                $(this).closest('tr').remove();
                calculateGrandTotals();
            });

            $(document).on('change', '.unit_price, .qty, .discount', function() {
                calculate($(this));
            });

            // Handle Sub Total field - update Qty live as user types and lock it
            $(document).on('input', '.sub_total', function() {
                updateQtyFromSubTotal($(this), false);
            });
            
            $(document).on('change', '.sub_total', function() {
                updateQtyFromSubTotal($(this), true);
            });

            function updateQtyFromSubTotal(subTotalField, triggerCalculation) {
                var row = subTotalField.closest('tr');
                var unitPriceField = row.find('.unit_price_excl_unformatted');
                var qtyField = row.find('.qty');
                var discountField = row.find('.discount');
                var unitVatRateField = row.find('.unit_vat_rate');
                var taxField = row.find('.tax');
                var taxUnformattedField = row.find('.tax_unformatted');
                var subTotalUnformattedField = row.find('.sub_total_unformatted');

                var taxRate = __read_number($('#tax_rate')) || 0;
                var enteredValue = (subTotalField.val() || '').toString().trim();
                var subTotal = __read_number(subTotalField);

                if (isNaN(subTotal)) {
                    subTotal = 0;
                }

                if (triggerCalculation) {
                    subTotal = writePrefixSubTotalNumber(subTotalField, subTotal);
                } else {
                    subTotal = roundByPrefixSubTotalDecimals(subTotal);
                }

                if (subTotalUnformattedField.length) {
                    subTotalUnformattedField.val(subTotal);
                }

                var unitPrice = __read_number(unitPriceField);
                var discount = __read_number(discountField);
                var discountedUnitPrice = unitPrice - discount;

                if (subTotal > 0 && discountedUnitPrice > 0) {
                    /*
                     * Use the configured Unit VAT value in the reverse calculation.
                     * Example: 323.73 + configured Unit VAT 58 = 381.73.
                     * 839,806 / 381.73 = 2,200 exactly.
                     *
                     * The previous calculation used 323.73 * 1.18 = 382.0014,
                     * which incorrectly produced Qty 2,198 and then overwrote the
                     * entered Sub Total.
                     */
                    var rawUnitVat = (discountedUnitPrice * taxRate) / 100;
                    var configuredUnitVat = roundByPrefixUnitVatDecimals(rawUnitVat);
                    var unitPriceWithVat = discountedUnitPrice + configuredUnitVat;

                    if (unitPriceWithVat > 0) {
                        var calculatedQty = roundByPrefixUnitVatDecimals(
                            subTotal / unitPriceWithVat
                        );

                        writePrefixUnitVatNumber(qtyField, calculatedQty);
                        qtyField.prop('readonly', false).prop('disabled', false)
                            .removeAttr('readonly disabled')
                            .data('locked', false);

                        unitVatRateField.val(formatByPrefixUnitVatDecimals(rawUnitVat));

                        var vatAmount = roundByPrefixSubTotalDecimals(
                            configuredUnitVat * calculatedQty
                        );
                        writePrefixSubTotalNumber(taxField, vatAmount);

                        if (taxUnformattedField.length) {
                            taxUnformattedField.val(vatAmount);
                        }

                        if (triggerCalculation) {
                            calculateGrandTotals();
                        }
                    }
                } else if (subTotal === 0 || !enteredValue) {
                    qtyField
                        .prop('readonly', false)
                        .prop('disabled', false)
                        .removeAttr('readonly disabled')
                        .data('locked', false);

                    writePrefixUnitVatNumber(qtyField, 0);
                    writePrefixSubTotalNumber(taxField, 0);

                    if (taxUnformattedField.length) {
                        taxUnformattedField.val(0);
                    }
                    if (subTotalUnformattedField.length) {
                        subTotalUnformattedField.val(0);
                    }

                    if (triggerCalculation) {
                        calculateGrandTotals();
                    }
                }
            }

            function calculate($this) {
                var unitPriceField = $($this).closest('tr').find('.unit_price_excl_unformatted');
                var qtyField = $($this).closest('tr').find('.qty');
                var discountField = $($this).closest('tr').find('.discount');
                var unitVatRateField = $($this).closest('tr').find('.unit_vat_rate');
                var subTotalField = $($this).closest('tr').find('.sub_total');

                var unitPrice = __read_number(unitPriceField);
                var qty = writePrefixUnitVatNumber(qtyField, __read_number(qtyField));
                var discount = __read_number(discountField);
                var unitVatRate = __read_number(unitVatRateField);

                var discountedUnitPrice = unitPrice - discount;

                var tax_rate = __read_number($("#tax_rate")) || 0; // Default tax rate to 0 if not found
                var rawUnitVat = ((discountedUnitPrice * tax_rate) / 100);
                var unit_vat = roundByPrefixUnitVatDecimals(rawUnitVat); // Calculate unit VAT
                var unit_vat_tax = (unitPrice * tax_rate) / 100;
                var vat = roundByPrefixSubTotalDecimals(unit_vat * qty); // Prefix-configured VAT amount

                var subTotal = (discountedUnitPrice + unit_vat) *
                    qty; // Calculate subTotal using the provided formula

                writePrefixSubTotalNumber($($this).closest('tr').find('.sub_total'), subTotal);
                $($this).closest('tr').find('.sub_total_unformatted').val(roundByPrefixSubTotalDecimals(subTotal));

                writePrefixSubTotalNumber($($this).closest('tr').find('.tax'), vat);
                $($this).closest('tr').find('.tax_unformatted').val(roundByPrefixSubTotalDecimals(vat));

                unitVatRateField.val(formatByPrefixUnitVatDecimals(rawUnitVat));

                calculateGrandTotals();
            }






            $(document).on('change', '.product_id', function() {
                var product_id = $(this).val();
                var $row = $(this).closest('tr');
                var unitPriceField = $row.find('.unit_price');
                var unitPriceFieldUnformatted = $row.find('.unit_price_unformatted');
                var unitPriceFieldExcl = $row.find('.unit_price_excl');
                var unitPriceFieldExclUnformatted = $row.find('.unit_price_excl_unformatted');

                if (!product_id) {
                    __write_number(unitPriceField, 0);
                    unitPriceFieldUnformatted.val(0);
                    __write_number(unitPriceFieldExcl, 0);
                    unitPriceFieldExclUnformatted.val(0);
                    unitPriceField.trigger('change');
                    return;
                }

                $.ajax({
                    url: '/vat-module/vat-invoice2/product-details/' + encodeURIComponent(product_id),
                    type: 'GET',
                    dataType: 'json',
                    global: false,
                    success: function(data) {
                        data = data || {};
                        var unitPrice = parseFloat(data.unit_price || 0);
                        var unitPriceExcl = parseFloat(data.unit_price_excl || 0);

                        __write_number(unitPriceField, unitPrice);
                        unitPriceFieldUnformatted.val(unitPrice);
                        __write_number(unitPriceFieldExcl, unitPriceExcl);
                        unitPriceFieldExclUnformatted.val(unitPriceExcl);
                        unitPriceField.trigger('change');
                    },
                    error: function() {
                        // Do not display a global route/module error. Keep the row
                        // usable and reset only this product's calculated values.
                        __write_number(unitPriceField, 0);
                        unitPriceFieldUnformatted.val(0);
                        __write_number(unitPriceFieldExcl, 0);
                        unitPriceFieldExclUnformatted.val(0);
                        unitPriceField.trigger('change');
                    }
                });
            });


            $(document).on('change', '#price_adjustment', function() {
                calculateGrandTotals();
            });

            // Simple number to words function (supports integers only)
            function numberToWordsProfessional(amount) {
                amount = Number(amount);
                if (isNaN(amount)) return '';

                let number = Math.floor(amount);
                let fraction = Math.round((amount - number) * 100);

                // Handle rounding overflow
                if (fraction === 100) {
                    number += 1;
                    fraction = 0;
                }

                const a = [
                    '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                    'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen',
                    'Eighteen', 'Nineteen'
                ];
                const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

                function inWords(n) {
                    if (n === 0) return 'Zero';
                    if (n < 20) return a[n];
                    if (n < 100)
                        return b[Math.floor(n / 10)] + (n % 10 ? ' ' + a[n % 10] : '');
                    if (n < 1000)
                        return a[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' ' + inWords(n % 100) : '');
                    if (n < 1000000)
                        return inWords(Math.floor(n / 1000)) + ' Thousand' + (n % 1000 ? ' ' + inWords(n % 1000) : '');
                    if (n < 1000000000)
                        return inWords(Math.floor(n / 1000000)) + ' Million' + (n % 1000000 ? ' ' + inWords(n % 1000000) : '');
                    return 'Number Too Large';
                }

                let words = 'Rupees ' + inWords(number);

                if (fraction > 0) {
                    words += ' and Cents ' + inWords(fraction);
                }

                words += ' Only';

                return words;
            }

            function calculateGrandTotals() {
                var vatTotal = 0;
                var grandTotal = 0;
                var grandTotalWithVat = 0;
                var price_adjustment = __read_number($("#price_adjustment"));
                var tax_rate = __read_number($("#tax_rate")) || 0;

                $('#issue_customer_bill_add_table tbody tr').each(function() {
                    var tax = __read_number($(this).find('.tax_unformatted'));
                    var subTotal = __read_number($(this).find('.sub_total_unformatted'));

                    vatTotal += tax;
                    grandTotal += subTotal;
                });

                // Tax Base Value is always displayed/posted to 2 decimals. Use
                // normal nearest-cent rounding, not truncation from prefix settings.
                function roundVatInvoice2Money2(value) {
                    var n = parseFloat(value || 0);
                    if (isNaN(n)) { n = 0; }
                    return Math.round((n + Number.EPSILON) * 100) / 100;
                }

                var taxBaseValue = tax_rate > 0
                    ? roundVatInvoice2Money2(grandTotal / (1 + (tax_rate / 100)))
                    : roundVatInvoice2Money2(grandTotal);

                // VAT = Total invoice Amount (with VAT) - rounded Tax Base Value.
                var vatAmount = roundVatInvoice2Money2(grandTotal - taxBaseValue);

                grandTotalWithVat += grandTotal - vatTotal;

                var final_grand_total = grandTotal + price_adjustment;

                __write_number($('#vat_total'), vatAmount, false, 2);
                writePrefixSubTotalNumber($('#grand_total'), grandTotal);
                __write_number($('#grand_total_with_vat'), taxBaseValue, false, 2);
                writePrefixSubTotalNumber($('#final_grand_total_with_vat'), final_grand_total);
                writePrefixSubTotalNumber($('#voucher_order_amount'), grandTotal);
                writePrefixSubTotalNumber($('#amount_0'), final_grand_total);
                var $wordsField = $("#final_grand_total_words");
                $wordsField.val(numberToWordsProfessional(final_grand_total));
                $wordsField.css('height', 'auto');
                if ($wordsField[0]) {
                    $wordsField.css('height', Math.max(58, $wordsField[0].scrollHeight) + 'px');
                }
                $("#amount_0").attr('readonly', true);
                $('#voucher_order_amount').trigger('change');
            }


        });
    </script>
@endsection
