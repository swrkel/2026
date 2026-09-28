@extends('layouts.app')
@section('title', !empty($is_purchase_order) ? 'Edit Purchase Order' : __('purchase.edit_purchase'))

@section('content')

    <style>
        .required-to-fill-label {
            color: #d9534f;
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
        }
        .purchase-save-row {
            position: sticky;
            bottom: 10px;
            z-index: 10;
            background: #fff;
            padding-top: 10px;
        }
        .purchase-floating-save {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 1050;
            min-width: 140px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
        }
        .purchase-page-has-floating-save {
            padding-bottom: 90px;
        }
    </style>


    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">{{ !empty($is_purchase_order) ? 'Edit Purchase Order' : __('purchase.edit_purchase') }} <i class="fa fa-keyboard-o hover-q text-muted"
                            aria-hidden="true" data-container="body" data-toggle="popover" data-placement="bottom"
                            data-content="@include('purchase.partials.keyboard_shortcuts_details')" data-html="true" data-trigger="hover"
                            data-original-title="" title=""></i></h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">Purchases</a></li>
                        <li><span>{{ !empty($is_purchase_order) ? 'Edit Purchase Order' : __('purchase.edit_purchase') }}</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content main-content-inner">

        <!-- Page level currency setting -->
        <input type="hidden" id="p_code" value="{{ $currency_details->code }}">
        <input type="hidden" id="p_symbol" value="{{ $currency_details->symbol }}">
        <input type="hidden" id="p_thousand" value="{{ $currency_details->thousand_separator }}">
        <input type="hidden" id="p_decimal" value="{{ $currency_details->decimal_separator }}">

        @include('layouts.partials.error')

        {!! Form::open([
            'url' => action('PurchaseController@update', [$purchase->id]),
            'method' => 'PUT',
            'id' => 'add_purchase_form',
            'files' => true,
        ]) !!}
        <input type="hidden" name="offline_mode" id="offline_mode" value="0">

        @php
            $currency_precision = config('constants.currency_precision', 2);
        @endphp

        <input type="hidden" id="purchase_id" value="{{ $purchase->id }}">
        <input type="hidden" name="is_purchase_order" id="is_purchase_order" value="{{ !empty($is_purchase_order) ? 1 : 0 }}">

        @component('components.widget', ['class' => 'box-primary'])
            <div class="row">
                {{-- Row 1, Col 1: Purchase No --}}
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('purchase_no', (!empty($is_purchase_order) ? __('purchase.purchase_order_no') : __('purchase.purchase_no')) . ':') !!}
                        {!! Form::text('invoice_no', !empty($purchase->invoice_no) ? $purchase->invoice_no : 1, [
                            'class' => 'form-control',
                        ]) !!}
                    </div>
                </div>

                {{-- Row 1, Col 2: Business Location --}}
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        @show_tooltip(__('tooltip.purchase_location'))
                        {!! Form::select('location_id', $business_locations, $purchase->location_id, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'disabled',
                        ]) !!}
                        {{-- Disabled fields don't submit. Keep these hidden inputs so update() receives them --}}
                        {!! Form::hidden('location_id', $purchase->location_id, ['id' => 'location_id_hidden']) !!}
                        {!! Form::hidden('store_id', $purchase->store_id, ['id' => 'store_id']) !!}
                    </div>
                </div>

                {{-- Row 1, Col 3: Purchase Status --}}
                <div class="col-sm-3">
                    <div class="form-group">
                        <span class="required-to-fill-label">Required to Fill</span>
                        {!! Form::label('status', __('purchase.purchase_status') . ':*') !!}
                        @show_tooltip(__('tooltip.order_status'))
                        {!! Form::select('status', $orderStatuses, $purchase->status, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'required',
                        ]) !!}
                    </div>
                </div>

                {{-- Row 1, Col 4: Supplier --}}
                <div class="col-sm-3">
                    <div class="form-group">
                        <span class="required-to-fill-label">Required to Fill</span>
                        {!! Form::label('supplier_id', __('purchase.supplier') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::select(
                                'contact_id',
                                $purchase->contact ? [$purchase->contact_id => $purchase->contact->name] : [],
                                $purchase->contact_id,
                                [
                                    'class' => 'form-control',
                                    'placeholder' => __('messages.please_select'),
                                    'required',
                                    'id' => 'supplier_id',
                                ],
                            ) !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default bg-white btn-flat add_new_supplier"
                                    data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Row 2, Col 1: P. Invoice No --}}
                <div class="col-sm-3">
                    <div class="form-group">
                        <span class="required-to-fill-label">Required to Fill</span>
                        {!! Form::label('ref_no', __('purchase.p_invoice_no') . ':') !!}
                        {!! Form::text('ref_no', $purchase->ref_no, ['class' => 'form-control', 'required', 'id' => 'ref_no']) !!}
                    </div>
                </div>

                {{-- Row 2, Col 2: Received Date --}}
                <div class="col-sm-3">
                    <div class="form-group">
                        <span class="required-to-fill-label">Required to Fill</span>
                        {!! Form::label('transaction_date', (!empty($is_purchase_order) ? 'Order Date' : __('purchase.purchase_date')) . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('transaction_date', @format_datetime($purchase->transaction_date), [
                                'class' => 'form-control',
                                'required',
                            ]) !!}
                        </div>
                    </div>
                </div>

                {{-- Row 2, Col 3: Invoice Date --}}
                <div class="col-sm-3">
                    <div class="form-group">
                        <span class="required-to-fill-label">Required to Fill</span>
                        {!! Form::label('invoice_date', __('purchase.invoice_date') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('invoice_date', @format_date($purchase->invoice_date), [
                                'class' => 'form-control',
                                'required',
                                'id' => 'invoice_date',
                                'readonly',
                            ]) !!}
                        </div>
                    </div>
                </div>

                {{-- Row 2, Col 4: VAT Invoice ? --}}
                <div class="col-sm-3">
                    <div class="form-group">
                        <span class="required-to-fill-label">Required to Fill</span>
                        {!! Form::label('is_vat', __('lang_v1.is_vat')) !!}
                        {!! Form::select('is_vat', ['0' => __('lang_v1.no'), '1' => __('lang_v1.yes')], $purchase->is_vat, [
                            'class' => 'form-control select2',
                            'required',
                        ]) !!}
                    </div>
                </div>

                {{-- Row 3, Col 1: Store --}}
                <div class="col-sm-3">
                    <div class="form-group">
                        <span class="required-to-fill-label">Required to Fill</span>
                        {!! Form::label('store_id_display', __('lang_v1.store_id') . ':*') !!}
                        <select name="store_id_display" id="store_id_display" class="form-control select2" disabled>
                            @if($purchase->warehouse)
                                <option value="{{ $purchase->store_id }}">{{ $purchase->warehouse->name }}</option>
                            @else
                                <option value="{{ $purchase->store_id }}">Main Store</option>
                            @endif
                        </select>
                    </div>
                </div>

                {{-- Row 3, Col 2: Pay Term --}}
                <div class="col-sm-3">
                    <div class="form-group">
                        <div class="multi-input">
                            {!! Form::label('pay_term_number', __('contact.pay_term') . ':') !!} @show_tooltip(__('tooltip.pay_term'))
                            <br />
                            {!! Form::number('pay_term_number', $purchase->pay_term_number, [
                                'class' => 'form-control width-40 pull-left',
                                'placeholder' => __('contact.pay_term'),
                            ]) !!}

                            {!! Form::select(
                                'pay_term_type',
                                ['months' => __('lang_v1.months'), 'days' => __('lang_v1.days')],
                                $purchase->pay_term_type,
                                [
                                    'class' => 'form-control width-60 pull-left',
                                    'placeholder' => __('messages.please_select'),
                                    'id' => 'pay_term_type',
                                ],
                            ) !!}
                        </div>
                    </div>
                </div>

                {{-- Row 3, Col 3: Attach Document --}}
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('document', __('purchase.attach_document') . ':') !!}
                        {!! Form::file('document', ['id' => 'upload_document']) !!}
                        <p class="help-block">Max File size: 256 MB</p>
                    </div>
                </div>

                {{-- Row 3, Col 4: Purchase order No --}}
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('order_no_display', __('purchase.purchase_order_no') . ':') !!}
                        {!! Form::text('order_no_display', !empty($purchase->purchase_order) ? $purchase->purchase_order->invoice_no : null, ['class' => 'form-control', 'readonly']) !!}
                    </div>
                </div>

                <!-- Currency Exchange Rate -->
                <div class="col-sm-3 @if (!$currency_details->purchase_in_diff_currency) hide @endif">
                    <div class="form-group">
                        {!! Form::label('exchange_rate', __('purchase.p_exchange_rate') . ':*') !!}
                        @show_tooltip(__('tooltip.currency_exchange_factor'))
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-info"></i>
                            </span>
                            {!! Form::number('exchange_rate', $purchase->exchange_rate, [
                                'class' => 'form-control',
                                'required',
                                'step' => 0.001,
                            ]) !!}
                        </div>
                        <span class="help-block text-danger">
                            @lang('purchase.diff_purchase_currency_help', ['currency' => $currency_details->name])
                        </span>
                    </div>
                </div>
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-primary'])
            <div class="row" hidden>
                <div class="col-sm-8 col-sm-offset-2">
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-search"></i>
                            </span>
                            {!! Form::text('search_product', null, [
                                'class' => 'form-control mousetrap',
                                'id' => 'search_product',
                                'placeholder' => __('lang_v1.search_product_placeholder'),
                                'autofocus',
                            ]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group">
                        <button tabindex="-1" type="button" class="btn btn-link btn-modal"
                            data-href="{{ action('ProductController@quickAdd') }}" data-container=".quick_add_product_modal"><i
                                class="fa fa-plus"></i> @lang('product.add_new_product') </button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12" style="margin-bottom: 10px;">
                    <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                        <label style="font-weight: normal; margin-bottom: 0; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; color: red;">
                            <input type="checkbox" name="free_qty_as_new_transaction" id="free_qty_as_new_transaction" value="1" @if(!empty($has_free_qty_transaction)) checked @endif style="width: 18px; height: 18px; margin: 0; vertical-align: middle;">
                            <strong>Free Qty</strong>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    @include('purchase.partials.edit_purchase_entry_row')

                    <hr />
                    <div class="pull-right col-md-5">
                        <table class="pull-right col-md-12">
                            <tr class="hide">
                                <th class="col-md-7 text-right">@lang('purchase.total_before_tax'):</th>
                                <td class="col-md-5 text-left">
                                    <span id="total_st_before_tax" class="display_currency"></span>
                                    <input type="hidden" id="st_before_tax_input" value=0>
                                </td>
                            </tr>
                            <tr>
                                <th class="col-md-7 text-right">@lang('purchase.net_total_amount'):</th>
                                <td class="col-md-5 text-left">
                                    <span id="total_subtotal"
                                        class="display_currency">{{ $purchase->total_before_tax / $purchase->exchange_rate }}</span>
                                    <!-- This is total before purchase tax-->
                                    <input type="hidden" id="total_subtotal_input"
                                        value="{{ $purchase->total_before_tax / $purchase->exchange_rate }}"
                                        name="total_before_tax">
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-primary'])
            <div class="row">
                <div class="col-sm-12">
                    <table class="table">
                        <tr>

                            <td class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('discount_amount', __('purchase.discount_amount') . ':') !!}
                                </div>
                            </td>
                            <td class="col-md-3">

                            </td>
                            <td class="col-md-3">
                                &nbsp;
                            </td>
                            <td class="col-md-3">
                                <b>Discount:</b>(-)
                                <span id="discount_calculated_amount" class="display_currency">0</span>
                                {!! Form::hidden(
                                    'discount_amount',
                                
                                    $purchase->discount_type == 'fixed'
                                        ? number_format(
                                            $purchase->discount_amount / $purchase->exchange_rate,
                                            $currency_precision,
                                            $currency_details->decimal_separator,
                                            $currency_details->thousand_separator,
                                        )
                                        : number_format(
                                            $purchase->discount_amount,
                                            $currency_precision,
                                            $currency_details->decimal_separator,
                                            $currency_details->thousand_separator,
                                        ),
                                    ['class' => 'form-control input_number'],
                                ) !!}
                            </td>
                        </tr>
                        <tr hidden>
                            <td>
                                <div class="form-group">
                                    {!! Form::label('tax_id', __('purchase.purchase_tax') . ':') !!}

                                </div>
                            </td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>
                                <b>@lang('purchase.purchase_tax'):</b>(+)
                                <span id="tax_calculated_amount" class="display_currency">0</span>
                                {!! Form::hidden('tax_amount', $purchase->tax_amount, ['id' => 'tax_amount']) !!}
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <div class="form-group">
                                    {!! Form::label('shipping_details', __('purchase.shipping_details') . ':') !!}
                                    {!! Form::text('shipping_details', $purchase->shipping_details, ['class' => 'form-control']) !!}
                                </div>
                            </td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td style="text-align: right;">
                                <div class="form-group" style="display: inline-flex; align-items: center; gap: 10px;">
                                    {!! Form::label('shipping_charges', '(+) ' . __('purchase.additional_shipping_charges') . ':', [
                                        'style' => 'margin-bottom: 0;',
                                    ]) !!}
                                    {!! Form::text(
                                        'shipping_charges',
                                        number_format(
                                            $purchase->shipping_charges / $purchase->exchange_rate,
                                            $currency_precision,
                                            $currency_details->decimal_separator,
                                            $currency_details->thousand_separator,
                                        ),
                                        ['class' => 'form-control input_number', 'style' => 'width: auto;'],
                                    ) !!}
                                </div>
                                <div class="form-group @if(!empty($is_purchase_order)) hide @endif" style="display: inline-flex; align-items: center; gap: 10px;">
                                    {!! Form::label('price_adjustment', __('purchase.price_adjustment') . ':', [
                                        'style' => 'margin-bottom: 0; color:red;',
                                    ]) !!}
                                    {!! Form::text(
                                        'price_adjustment',
                                        number_format(
                                            $purchase->price_adjustment / $purchase->exchange_rate,
                                            $currency_precision,
                                            $currency_details->decimal_separator,
                                            $currency_details->thousand_separator,
                                        ),
                                        ['class' => 'form-control input_number', 'style' => 'width: auto; outline: 1px solid red; color:red;'],
                                    ) !!}
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="apply_free_product_total" value="1" @if(!empty($has_free_product_entry)) checked @endif>
                                        Include free product total in accounting entries
                                    </label>
                                </div>
                                {!! Form::hidden('final_total', $purchase->final_total, ['id' => 'grand_total_hidden']) !!}
                                <b>@lang('purchase.purchase_total'): </b><span id="grand_total" class="display_currency"
                                    data-currency_symbol='true'>{{ number_format(
                                        $purchase->final_total,
                                        $currency_precision,
                                        $currency_details->decimal_separator,
                                        $currency_details->thousand_separator,
                                    ) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <div class="form-group">
                                    {!! Form::label('additional_notes', __('purchase.additional_notes')) !!}
                                    {!! Form::textarea('additional_notes', $purchase->additional_notes, ['class' => 'form-control', 'rows' => 3]) !!}
                                </div>
                            </td>
                        </tr>

                    </table>
                </div>
            </div>
        @endcomponent

        @if (empty($is_purchase_order))
        @component('components.widget', [
            'class' => 'box-primary unload_div hide',
            'title' => __('purchase.unload_tanks'),
        ])
            <div class="box-body unload_tank">
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-primary'])
            <div class="box-body payment_row" data-row_id="0">
                @if (!empty($is_admin))
                    @if (!empty($purchase->payment_lines))
                        @foreach ($purchase->payment_lines as $index => $one)
                            @include('sale_pos.partials.payment_row_form_expense', [
                                'row_index' => $index,
                                'payment' => $one,
                                'edit' => 1,
                            ])
                        @endforeach
                    @else
                        @include('sale_pos.partials.payment_row_form_expense', [
                            'row_index' => 0,
                            'edit' => 1,
                        ])
                    @endif
                @endif
                <hr>

                <div class="row">
                    <div class="col-sm-12">
                        <div class="pull-right"><strong>@lang('purchase.payment_due'):</strong> <span id="payment_due">0.00</span></div>
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-sm-6">
                        <a id="" href="{{ url('purchases') }}"
                            class="btn btn-danger pull-left btn-flat">@lang('lang_v1.back')</a>
                    </div>
                    <div class="col-sm-6">
                        <button type="button" id="submit_purchase_form"
                            class="btn btn-primary pull-right btn-flat">@lang('messages.save')</button>
                    </div>
                </div>
            </div>
        @endcomponent
        @else
        @component('components.widget', ['class' => 'box-primary'])
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-6">
                        <a id="" href="{{ url('purchases') }}"
                            class="btn btn-danger pull-left btn-flat">@lang('lang_v1.back')</a>
                    </div>
                    <div class="col-sm-6">
                        <button type="button" id="submit_purchase_form"
                            class="btn btn-primary pull-right btn-flat">@lang('messages.save')</button>
                    </div>
                </div>
            </div>
        @endcomponent
        @endif
        <input type="hidden" name="cash_account_id" id="cash_account_id" value="{{ $cash_account_id }}">
        <input type="hidden" name="is_edit" id="is_edit" value="1">
        {!! Form::close() !!}
        @if(!empty($is_purchase_order))
        <button type="button" id="submit_purchase_form_floating"
            class="btn btn-primary btn-flat purchase-floating-save">@lang('messages.save')</button>
        @endif
    </section>
    <!-- @eng START 15/2 -->
    <style>
        .swal-title {
            color: red;
        }
    </style>
    <!-- @eng END 15/2 -->
    <!-- /.content -->
    <!-- quick product modal -->
    <div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>
    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('contact.create', ['quick_add' => true])
    </div>

@endsection

<!-- discount amount modal -->
    <div class="modal fade" id="discountAmountEditModal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">

                <div class="modal-header">
                    <h4 class="modal-title">Enter Discount Amount</h4>
                </div>

                <div class="modal-body">
                    <input type="number" step="any" class="form-control" id="discount_amount_input">
                    <input type="hidden" id="discount_row_index">
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" id="saveDiscountAmount">
                        Apply
                    </button>
                    <button class="btn btn-default" data-dismiss="modal">
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="confirm_purchase_tax_change_modal" tabindex="-1" role="dialog" aria-labelledby="confirmPurchaseTaxChangeModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="confirmPurchaseTaxChangeModalLabel">@lang('messages.confirmation')</h4>
                </div>
                <div class="modal-body">
                    You are going to change the Tax settings for this product. Are you sure to change?
                </div>
                <div class="modal-footer" style="display:flex; justify-content:space-between;">
                    <button type="button" class="btn btn-primary" id="confirm_purchase_tax_change_yes">Yes</button>
                    <button type="button" class="btn btn-default" id="confirm_purchase_tax_change_no" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>

@section('javascript')
    @if(auth()->user()->can('offline.access') && ($can_offline_access ?? false))
    <script>
        window.APP_CAN_OFFLINE_SYNC_MANAGE = {{ ($can_offline_sync_manage ?? false) ? 'true' : 'false' }};
    </script>
    <script src="{{ asset('js/offline-queue.js?v=' . ($asset_v ?? 1)) }}"></script>
    <script src="{{ asset('js/offline-wrapper.js?v=' . ($asset_v ?? 1)) }}"></script>
    @endif
    <script src="{{ asset('js/purchase.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $(".purchase_line_tax_id").trigger('change');
            update_table_total();
            update_grand_total();
            $('#method_0').trigger('change');
        });
    </script>
    <script>
        const currency_precision = {{ session('business.currency_precision') ?? 2 }};

        $(document).on('click', '.open-discount-modal', function() {
            let row = $(this).closest('tr');
            let rowIndex = row.index();

            let currentDiscount = row.find('.discount_amount').val() || 0;

            $('#discount_amount_input').val(
                parseFloat(currentDiscount).toFixed(currency_precision)
            );

            $('#discount_row_index').val(rowIndex);
            $('#discountAmountEditModal').modal('show');
        });

        $('#saveDiscountAmount').on('click', function () {

            let discountAmount = parseFloat($('#discount_amount_input').val()) || 0;
            let rowIndex = $('#discount_row_index').val();

            let row = $('#purchase_entry_table tbody tr').eq(rowIndex);

            row.find('.discount_amount').val(
                discountAmount.toFixed(currency_precision)
            );

            row.find('.discount_percent').val(0);

            let qty = parseFloat(row.find('.purchase_quantity').val()) || 1;

            let unitCost = parseFloat(
                row.find('.purchase_unit_cost_without_discount').val()
            ) || 0;

            let newUnitCost = unitCost - (discountAmount / qty);
            newUnitCost = newUnitCost.toFixed(currency_precision);

            row.find('.purchase_unit_cost').val(newUnitCost);

            update_row_price_for_exchange_rate(row);
            update_inline_profit_percentage(row);
            update_table_total();
            update_grand_total();

            $('#discountAmountEditModal').modal('hide');
        });

    </script>
    @include('purchase.partials.keyboard_shortcuts')

    @php
        $i = 0;
    @endphp
    @foreach ($purchase->purchase_lines as $purchase_line)
        @if (!empty($purchase_line->product->category->name) && $purchase_line->product->category->name == 'Fuel')
            <script>
                $(document).ready(function() {
                    $.ajax({
                        method: 'get',
                        url: '/purchases/get_edit_unload_tank_row',
                        data: {
                            product_id: {{ $purchase_line->product_id }},
                            transaction_id: {{ $purchase_line->transaction_id }},
                            location_id: $('#location_id').val(),
                            row_count: {{ $i }},
                        },
                        success: function(data) {
                            if (data) {
                                $('.unload_div').removeClass('hide');
                                $('.unload_tank').append(data);
                            }
                        },
                    });
                    $('.method').trigger('change');
                });
            </script>
        @endif
        @php
            $i++;
        @endphp
    @endforeach

    <script>
        $(document).ready(function() {
            $('.payment-amount').change(function() {

                paid = parseFloat($(this).val());
                var $row = $(this).closest('.row');
                var accid = parseInt($row.find('.payment_types_dropdown').val());

                $.ajax({
                    method: 'GET',
                    url: '/finance/check-insufficient-balance-for-accounts',
                    success: function(result) {
                        var ids = result;

                        if (ids.includes(accid)) {

                            $.ajax({
                                method: 'GET',
                                url: '/finance/get-account-balance/' + accid,
                                success: function(result) {

                                    if (parseFloat(paid) > parseFloat(result
                                            .balance) || result.balance == null) {
                                        swal({
                                            title: 'Insufficient Balance',
                                            icon: "error",
                                            buttons: true,
                                            dangerMode: true,
                                        })

                                        $('button#submit_purchase_form').prop(
                                            'disabled', true);
                                        return false;
                                    } else {
                                        $('button#submit_purchase_form').prop(
                                            'disabled', false);
                                    }
                                }
                            });
                        } else {
                            $('button#submit_purchase_form').prop('disabled', false);
                        }

                    }
                });

                // @eng END 15/2 

            });

            $('#transaction_date_range_cheque_deposit').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#transaction_date_range_cheque_deposit').val(start.format(moment_date_format) + ' ~ ' +
                        end.format(moment_date_format));

                    get_cheques_list();
                }
            );





            $('.account_id').change(function() {

                var accid = parseInt($(this).val());
                var $row = $(this).closest('.row');
                var paid = parseFloat($row.find('.payment-amount').val());

                if ($(this).val() == "cheque") {
                    $row.find('.payment-amount').prop('readonly', true);
                } else {
                    $row.find('.payment-amount').prop('readonly', false);
                }

                $.ajax({
                    method: 'GET',
                    url: '/finance/check-insufficient-balance-for-accounts',
                    success: function(result) {
                        var ids = result;
                        if (ids.includes(accid)) {

                            $.ajax({
                                method: 'GET',
                                url: '/finance/get-account-balance/' + accid,
                                success: function(result) {

                                    if (parseFloat(paid) > parseFloat(result
                                            .balance) || result.balance == null) {
                                        swal({
                                            title: 'Insufficient Balance',
                                            icon: "error",
                                            buttons: true,
                                            dangerMode: true,
                                        })

                                        $('button#submit_purchase_form').prop(
                                            'disabled', true);
                                        return false;
                                    } else {
                                        $('button#submit_purchase_form').prop(
                                            'disabled', false);
                                    }
                                }
                            });
                        } else {
                            $('button#submit_purchase_form').prop('disabled', false);
                        }

                    }
                });

            });
           function lockForm() {
        let refVal = $('#ref_no').val().trim();

        if (!refVal) {
            // Disable everything except ref_no
            $('input, select, textarea, button').not('#ref_no').prop('disabled', true);
            $('#ref_no').addClass('is-invalid').focus();
        } else {
            // Enable everything when filled
            $('input, select, textarea, button').prop('disabled', false);
            $('#ref_no').removeClass('is-invalid');
        }
    }

    // Initial check on page load
    lockForm();

    // Monitor changes to ref_no
    $('#ref_no').on('input blur keydown', function(e) {
        // Prevent tabbing away if empty
        if (!$(this).val().trim() && e.key === "Tab") {
            e.preventDefault();
            $(this).focus();
        }
        lockForm();
    });

    // Prevent form submission if ref_no empty
    $('#submit_purchase_form').on('click', function(e) {
        if (!$('#ref_no').val().trim()) {
            e.preventDefault();
            swal('Enter Purchase Order No before proceeding.');
            lockForm();
        }
    });

    @if(!empty($is_purchase_order))
    $('body').addClass('purchase-page-has-floating-save');

    function syncFloatingPurchaseSaveButton() {
        var $mainButton = $('#submit_purchase_form');
        var $floatingButton = $('#submit_purchase_form_floating');

        if (!$mainButton.length || !$floatingButton.length) {
            return;
        }

        $floatingButton.prop('disabled', $mainButton.prop('disabled'));
        $floatingButton.text($.trim($mainButton.text()) || 'Save');
    }

    $('#submit_purchase_form_floating').on('click', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        // Trigger click on the main save button to perform all standard validation & lock checks
        $('#submit_purchase_form').trigger('click');
    });

    syncFloatingPurchaseSaveButton();

    var submitPurchaseButton = document.getElementById('submit_purchase_form');
    if (submitPurchaseButton && window.MutationObserver) {
        new MutationObserver(function() {
            syncFloatingPurchaseSaveButton();
        }).observe(submitPurchaseButton, {
            attributes: true,
            attributeFilter: ['disabled']
        });
    }
    @endif
        });
    </script>
@endsection
